/**
 * Live Dashboard WebSocket Client
 * Connects to the WebSocket server to receive real-time updates for the doctor dashboard
 */

// WebSocket connection
let socket = null;
let reconnectInterval = null;
const WEBSOCKET_URL = 'ws://localhost:8080'; // Change to your server address

// Topic subscriptions - add the topics you want to subscribe to
const topics = ['patients', 'visits', 'vitals'];

// Initialize WebSocket connection
function initWebSocket() {
    // Close existing connection if any
    if (socket) {
        socket.close();
    }
    
    // Create new WebSocket connection
    socket = new WebSocket(WEBSOCKET_URL);
    
    // Connection opened
    socket.addEventListener('open', (event) => {
        console.log('WebSocket connection established');
        clearInterval(reconnectInterval);
        
        // Subscribe to topics
        subscribeToTopics();
    });
    
    // Listen for messages
    socket.addEventListener('message', (event) => {
        console.log('Message from server:', event.data);
        
        // Parse message data
        const data = JSON.parse(event.data);
        
        // Handle different message types
        handleWebSocketMessage(data);
    });
    
    // Connection closed
    socket.addEventListener('close', (event) => {
        console.log('WebSocket connection closed');
        
        // Try to reconnect every 5 seconds
        if (!reconnectInterval) {
            reconnectInterval = setInterval(() => {
                console.log('Attempting to reconnect...');
                initWebSocket();
            }, 5000);
        }
    });
    
    // Connection error
    socket.addEventListener('error', (event) => {
        console.error('WebSocket error:', event);
    });
}

// Subscribe to topics
function subscribeToTopics() {
    if (socket && socket.readyState === WebSocket.OPEN) {
        // Create subscription message
        const subscriptionMsg = {
            action: 'subscribe',
            topics: topics
        };
        
        // Send subscription request
        socket.send(JSON.stringify(subscriptionMsg));
    }
}

// Handle incoming WebSocket messages
function handleWebSocketMessage(data) {
    // Check if it's a subscription confirmation
    if (data.type === 'subscription_confirmation') {
        console.log('Subscribed to topics:', data.topics);
        return;
    }
    
    // Handle other message types based on their type field
    switch (data.type) {
        case 'new_patients':
            // New patients were added
            handleNewPatients(data.data);
            break;
            
        case 'patient_updated':
            // A patient was updated
            handlePatientUpdated(data.data);
            break;
            
        case 'new_visit':
            // A new visit was recorded
            handleNewVisit(data.data);
            break;
            
        case 'vitals_updated':
            // Patient vitals were updated
            handleVitalsUpdated(data.data);
            break;
            
        case 'new_visits':
            // Multiple new visits
            handleNewVisits(data.data);
            break;
            
        case 'updated_vitals':
            // Multiple patients' vitals updated
            handleUpdatedVitals(data.data);
            break;
    }
}

// Handler for new patients
function handleNewPatients(patients) {
    // Get the patient list container
    const patientList = document.getElementById('patient-list');
    if (!patientList) return;
    
    // Add notification
    showNotification(`${patients.length} new patient(s) added`);
    
    // Add new patients to the list without full page reload
    patients.forEach(patient => {
        // Check if patient already exists
        if (!document.getElementById(`patient-${patient.patient_id}`)) {
            // Create new patient element
            const patientElement = createPatientElement(patient);
            patientList.prepend(patientElement);
        }
    });
}

// Handler for patient updates
function handlePatientUpdated(patient) {
    // Get the patient element
    const patientElement = document.getElementById(`patient-${patient.patient_id}`);
    if (patientElement) {
        // Update patient information
        updatePatientElement(patientElement, patient);
    } else {
        // If viewing patient list, add to the list
        const patientList = document.getElementById('patient-list');
        if (patientList) {
            patientList.prepend(createPatientElement(patient));
        }
    }
    
    // If we're viewing this specific patient, update the details
    const patientDetails = document.getElementById(`patient-details-${patient.patient_id}`);
    if (patientDetails) {
        updatePatientDetails(patientDetails, patient);
    }
    
    // Show notification
    showNotification(`Patient ${patient.name} updated`);
}

// Handler for new visit
function handleNewVisit(visit) {
    // Show notification
    showNotification(`New visit recorded for patient ID: ${visit.patient_id}`);
    
    // If we're viewing this patient's visits, add to the list
    const visitsList = document.getElementById(`visits-list-${visit.patient_id}`);
    if (visitsList) {
        visitsList.prepend(createVisitElement(visit));
    }
    
    // If we're viewing the visits dashboard, add to the global list
    const globalVisitsList = document.getElementById('global-visits-list');
    if (globalVisitsList) {
        globalVisitsList.prepend(createVisitElement(visit));
    }
}

// Handler for vitals updates
function handleVitalsUpdated(vitals) {
    // Get patient ID
    const patientId = vitals.patient_id;
    
    // If we're viewing this patient's vitals, update them
    const vitalsContainer = document.getElementById(`vitals-${patientId}`);
    if (vitalsContainer) {
        updateVitalsContainer(vitalsContainer, vitals);
    }
    
    // Show notification
    showNotification(`Vitals updated for patient ID: ${patientId}`);
}

// Handler for multiple new visits
function handleNewVisits(visits) {
    visits.forEach(visit => {
        handleNewVisit(visit);
    });
}

// Handler for multiple vitals updates
function handleUpdatedVitals(updatedVitals) {
    updatedVitals.forEach(vitals => {
        handleVitalsUpdated(vitals);
    });
}

// Helper function to create a patient element
function createPatientElement(patient) {
    // This will vary based on your HTML structure
    const element = document.createElement('div');
    element.id = `patient-${patient.patient_id}`;
    element.className = 'patient-card';
    element.innerHTML = `
        <div class="patient-name">${patient.name}</div>
        <div class="patient-details">
            <span class="age">${patient.age} years</span>
            <span class="gender">${patient.gender}</span>
        </div>
        <div class="patient-phone">${patient.phone_number}</div>
        <button class="view-btn" onclick="viewPatient(${patient.patient_id})">View Details</button>
    `;
    return element;
}

// Helper function to update a patient element
function updatePatientElement(element, patient) {
    // Update name and details
    element.querySelector('.patient-name').textContent = patient.name;
    element.querySelector('.age').textContent = `${patient.age} years`;
    element.querySelector('.gender').textContent = patient.gender;
    element.querySelector('.patient-phone').textContent = patient.phone_number;
}

// Helper function to update patient details
function updatePatientDetails(detailsContainer, patient) {
    // This will vary based on your HTML structure
    const vitalsSection = detailsContainer.querySelector('.vitals-section');
    if (vitalsSection) {
        updateVitalsContainer(vitalsSection, patient);
    }
    
    // Update other patient information
    const basicInfoSection = detailsContainer.querySelector('.basic-info');
    if (basicInfoSection) {
        const nameElement = basicInfoSection.querySelector('.patient-name');
        if (nameElement) nameElement.textContent = patient.name;
        
        const ageElement = basicInfoSection.querySelector('.patient-age');
        if (ageElement) ageElement.textContent = `${patient.age} years`;
        
        const genderElement = basicInfoSection.querySelector('.patient-gender');
        if (genderElement) genderElement.textContent = patient.gender;
        
        const phoneElement = basicInfoSection.querySelector('.patient-phone');
        if (phoneElement) phoneElement.textContent = patient.phone_number;
    }
}

// Helper function to update vitals container
function updateVitalsContainer(container, vitals) {
    // Update blood pressure
    const bpElement = container.querySelector('.blood-pressure .value');
    if (bpElement && vitals.blood_pressure) {
        bpElement.textContent = vitals.blood_pressure;
    }
    
    // Update temperature
    const tempElement = container.querySelector('.temperature .value');
    if (tempElement && vitals.temperature) {
        tempElement.textContent = `${vitals.temperature} °C`;
    }
    
    // Update weight
    const weightElement = container.querySelector('.weight .value');
    if (weightElement && vitals.weight) {
        weightElement.textContent = `${vitals.weight} kg`;
    }
    
    // Update pulse rate
    const pulseElement = container.querySelector('.pulse-rate .value');
    if (pulseElement && vitals.pulse_rate) {
        pulseElement.textContent = `${vitals.pulse_rate} bpm`;
    }
    
    // Update respiratory rate
    const respElement = container.querySelector('.respiratory-rate .value');
    if (respElement && vitals.respiratory_rate) {
        respElement.textContent = `${vitals.respiratory_rate} bpm`;
    }
}

// Helper function to create a visit element
function createVisitElement(visit) {
    const element = document.createElement('div');
    element.className = 'visit-item';
    element.innerHTML = `
        <div class="visit-date">${visit.appointment_date} at ${visit.appointment_time}</div>
        <div class="visit-vitals">
            <span class="vital-item">BP: ${visit.blood_pressure || 'N/A'}</span>
            <span class="vital-item">Temp: ${visit.temperature || 'N/A'} °C</span>
            <span class="vital-item">Weight: ${visit.weight || 'N/A'} kg</span>
        </div>
        <div class="visit-notes">${visit.notes || 'No notes'}</div>
    `;
    return element;
}

// Show notification
function showNotification(message) {
    // Check if notification element exists, or create it
    let notificationElement = document.getElementById('live-notification');
    if (!notificationElement) {
        notificationElement = document.createElement('div');
        notificationElement.id = 'live-notification';
        document.body.appendChild(notificationElement);
        
        // Add styles if not already defined in CSS
        notificationElement.style.position = 'fixed';
        notificationElement.style.bottom = '20px';
        notificationElement.style.right = '20px';
        notificationElement.style.padding = '10px 15px';
        notificationElement.style.backgroundColor = '#4CAF50';
        notificationElement.style.color = 'white';
        notificationElement.style.borderRadius = '4px';
        notificationElement.style.boxShadow = '0 2px 5px rgba(0,0,0,0.2)';
        notificationElement.style.opacity = '0';
        notificationElement.style.transition = 'opacity 0.3s ease-in-out';
        notificationElement.style.zIndex = '9999';
    }
    
    // Set message and show notification
    notificationElement.textContent = message;
    notificationElement.style.opacity = '1';
    
    // Hide after 3 seconds
    setTimeout(() => {
        notificationElement.style.opacity = '0';
    }, 3000);
}

// Initialize WebSocket when the page loads
document.addEventListener('DOMContentLoaded', () => {
    initWebSocket();
    
    // Add styles for notifications
    const style = document.createElement('style');
    style.textContent = `
        #live-notification {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 10px 15px;
            background-color: #4CAF50;
            color: white;
            border-radius: 4px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.2);
            opacity: 0;
            transition: opacity 0.3s ease-in-out;
            z-index: 9999;
        }
    `;
    document.head.appendChild(style);
}); 