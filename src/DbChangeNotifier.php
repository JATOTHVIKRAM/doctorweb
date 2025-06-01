<?php
namespace Hospital;

use React\EventLoop\LoopInterface;

class DbChangeNotifier {
    private $webSocketServer;
    private $loop;
    private $db;
    private $lastPatientCount = 0;
    private $lastVisitCount = 0;
    private $pollingInterval = 2; // seconds

    public function __construct(WebSocketServer $webSocketServer, LoopInterface $loop) {
        $this->webSocketServer = $webSocketServer;
        $this->loop = $loop;
        
        // Connect to the same database as in api_nurse.php
        $this->db = new \mysqli("localhost", "root", "Vikram@2005", "hospital_database");
        
        if ($this->db->connect_error) {
            die("Database connection failed: " . $this->db->connect_error);
        }
        
        // Initialize last counts
        $this->updateLastCounts();
    }
    
    public function startMonitoring() {
        // Set up periodic checks for database changes
        $this->loop->addPeriodicTimer($this->pollingInterval, function () {
            $this->checkForChanges();
        });
        
        echo "Database change monitoring started\n";
    }
    
    private function updateLastCounts() {
        // Get current counts
        $patientQuery = "SELECT COUNT(*) as count FROM Patients";
        $visitQuery = "SELECT COUNT(*) as count FROM multi_visited_patients";
        
        $patientResult = $this->db->query($patientQuery);
        $visitResult = $this->db->query($visitQuery);
        
        if ($patientResult) {
            $this->lastPatientCount = $patientResult->fetch_assoc()['count'];
        }
        
        if ($visitResult) {
            $this->lastVisitCount = $visitResult->fetch_assoc()['count'];
        }
    }
    
    private function checkForChanges() {
        // Check for changes in patient count
        $patientQuery = "SELECT COUNT(*) as count FROM Patients";
        $result = $this->db->query($patientQuery);
        
        if ($result) {
            $currentCount = $result->fetch_assoc()['count'];
            
            if ($currentCount > $this->lastPatientCount) {
                // New patients added - fetch new patients
                $newPatientsQuery = "SELECT * FROM Patients ORDER BY patient_id DESC LIMIT " . ($currentCount - $this->lastPatientCount);
                $newPatientsResult = $this->db->query($newPatientsQuery);
                
                if ($newPatientsResult) {
                    $newPatients = [];
                    while ($row = $newPatientsResult->fetch_assoc()) {
                        $newPatients[] = $row;
                    }
                    
                    // Broadcast to patients topic
                    $this->webSocketServer->broadcastToTopic('patients', [
                        'type' => 'new_patients',
                        'data' => $newPatients
                    ]);
                    
                    echo "Detected {$currentCount - $this->lastPatientCount} new patient(s)\n";
                }
                
                $this->lastPatientCount = $currentCount;
            }
        }
        
        // Check for changes in visit count
        $visitQuery = "SELECT COUNT(*) as count FROM multi_visited_patients";
        $result = $this->db->query($visitQuery);
        
        if ($result) {
            $currentCount = $result->fetch_assoc()['count'];
            
            if ($currentCount > $this->lastVisitCount) {
                // New visits - fetch new visits
                $newVisitsQuery = "SELECT mvp.*, p.name as patient_name 
                                  FROM multi_visited_patients mvp 
                                  LEFT JOIN Patients p ON mvp.patient_id = p.patient_id 
                                  ORDER BY mvp.id DESC LIMIT " . ($currentCount - $this->lastVisitCount);
                $newVisitsResult = $this->db->query($newVisitsQuery);
                
                if ($newVisitsResult) {
                    $newVisits = [];
                    while ($row = $newVisitsResult->fetch_assoc()) {
                        $newVisits[] = $row;
                    }
                    
                    // Broadcast to visits topic
                    $this->webSocketServer->broadcastToTopic('visits', [
                        'type' => 'new_visits',
                        'data' => $newVisits
                    ]);
                    
                    // Also broadcast to specific patient topics
                    foreach ($newVisits as $visit) {
                        $patientId = $visit['patient_id'];
                        $this->webSocketServer->broadcastToTopic("patient_$patientId", [
                            'type' => 'new_visit',
                            'data' => $visit
                        ]);
                    }
                    
                    echo "Detected {$currentCount - $this->lastVisitCount} new visit(s)\n";
                }
                
                $this->lastVisitCount = $currentCount;
            }
        }
        
        // Check for vital updates
        $this->checkVitalUpdates();
    }
    
    private function checkVitalUpdates() {
        // Get patients with recently updated vitals (last 5 seconds)
        $vitalUpdatesQuery = "SELECT * FROM Patients 
                             WHERE updated_at >= DATE_SUB(NOW(), INTERVAL " . ($this->pollingInterval + 1) . " SECOND)";
        $result = $this->db->query($vitalUpdatesQuery);
        
        if ($result && $result->num_rows > 0) {
            $updatedPatients = [];
            while ($row = $result->fetch_assoc()) {
                $updatedPatients[] = $row;
                
                // Broadcast to specific patient topic
                $patientId = $row['patient_id'];
                $this->webSocketServer->broadcastToTopic("patient_$patientId", [
                    'type' => 'vitals_updated',
                    'data' => $row
                ]);
            }
            
            // Broadcast to vitals topic
            $this->webSocketServer->broadcastToTopic('vitals', [
                'type' => 'updated_vitals',
                'data' => $updatedPatients
            ]);
            
            echo "Detected vitals updates for " . count($updatedPatients) . " patient(s)\n";
        }
    }
} 