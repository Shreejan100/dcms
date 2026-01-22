// Common JavaScript functions for DCMS

// Show confirmation dialog before deleting items
function confirmDelete(message = 'Are you sure you want to delete this item?') {
    return confirm(message);
}

// Format date to YYYY-MM-DD
function formatDate(date) {
    return date.toISOString().split('T')[0];
}

// Check if two time ranges overlap
function isTimeOverlap(start1, end1, start2, end2) {
    return start1 < end2 && start2 < end1;
}

// Validate appointment form
function validateAppointmentForm() {
    const date = document.getElementById('appointment_date').value;
    const time = document.getElementById('appointment_time').value;
    
    if (!date || !time) {
        alert('Please select both date and time');
        return false;
    }

    const selectedDate = new Date(date);
    const today = new Date();
    today.setHours(0, 0, 0, 0);

    if (selectedDate < today) {
        alert('Please select a future date');
        return false;
    }

    return true;
}

// Update appointment status
function updateAppointmentStatus(appointmentId, status) {
    if (confirm('Are you sure you want to update this appointment status?')) {
        document.getElementById('appointment_id').value = appointmentId;
        document.getElementById('status').value = status;
        document.getElementById('update_status_form').submit();
    }
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    var tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
