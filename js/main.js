document.addEventListener('DOMContentLoaded', function() {
    // Task completion toggle
    const taskCheckboxes = document.querySelectorAll('.task-checkbox');
    taskCheckboxes.forEach(checkbox => {
        checkbox.addEventListener('change', function() {
            const taskId = this.dataset.taskId;
            const taskItem = this.closest('.task-item');
            
            fetch('api/tasks.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    action: 'toggle',
                    taskId: taskId,
                    completed: this.checked
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    taskItem.classList.toggle('task-completed');
                }
            })
            .catch(error => console.error('Error:', error));
        });
    });

    // Event deletion
    const deleteButtons = document.querySelectorAll('.delete-event');
    deleteButtons.forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            if (confirm('Möchten Sie diesen Termin wirklich löschen?')) {
                const eventId = this.dataset.eventId;
                
                fetch('api/events.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                    },
                    body: JSON.stringify({
                        action: 'delete',
                        eventId: eventId
                    })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        this.closest('.event-item').remove();
                    }
                })
                .catch(error => console.error('Error:', error));
            }
        });
    });

    // Form validation
    const forms = document.querySelectorAll('form');
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('[required]');
            let isValid = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    isValid = false;
                    field.classList.add('border-red-500');
                } else {
                    field.classList.remove('border-red-500');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Bitte füllen Sie alle erforderlichen Felder aus.');
            }
        });
    });
}); 