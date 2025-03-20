<?php
$koneksi = mysqli_connect("localhost", "root", "pacarjaemin", "todo_list");

if (isset($_GET['id'])) {
    $id = $_GET['id'];
    $query = mysqli_query($koneksi, "SELECT * FROM task WHERE id = '$id'");
    $task = mysqli_fetch_assoc($query);

    if (!$task) {
        echo "<script>alert('Task tidak ditemukan!'); window.location='index.php';</script>";
        exit;
    }
}

if (isset($_POST['update_task'])) {
    $id = $_POST['id']; 
    $task_name = $_POST['task'];
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'];
    $status = $_POST['status'];
    $current_date = date('Y-m-d');

    // Validasi tanggal deadline
    if ($due_date < $current_date) {
        echo "<script>
            alert('Deadline tidak boleh kurang dari tanggal hari ini!');
            document.getElementById('validation_error').style.display = 'block';
        </script>";
    } else {
        $update = mysqli_query($koneksi, "UPDATE task SET task='$task_name', priority='$priority', due_date='$due_date', status='$status' WHERE id='$id'");

        if ($update) {
            echo "<script>alert('Task berhasil diperbarui!'); window.location='index.php';</script>";
        } else {
            echo "<script>alert('Gagal memperbarui task!');</script>";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Task</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        body {
            background: linear-gradient(135deg, #1f1f1f, #3a3a3a);
            color: #fff;
            font-family: 'Poppins', sans-serif;
        }
        .container {
            max-width: 600px;
        }
        .form-control, .form-select {
            background-color: rgba(255, 255, 255, 0.1);
            color: white;
            border: 1px solid #444;
        }
        .form-control:focus, .form-select:focus {
            background-color: rgba(255, 255, 255, 0.2);
            color: white;
            border-color: #666;
            box-shadow: 0 0 0 0.25rem rgba(255, 255, 255, 0.25);
        }
        /* Override date input color */
        input[type="date"] {
            color-scheme: dark;
        }
        .alert-warning {
            background-color: rgba(255, 193, 7, 0.2);
            color: #ffc107;
            border-color: #ffc107;
        }
        /* Modal styles */
        .modal-content {
            background: rgba(40, 40, 40, 0.95);
            color: white;
            border-radius: 15px;
        }
        .modal-header {
            border-bottom: 1px solid #444;
        }
        .modal-footer {
            border-top: 1px solid #444;
        }
    </style>
</head>
<body>
    <div class="container mt-5">
        <h2 class="text-center">✏️ Edit Task</h2>
        
        <div id="validation_error" class="alert alert-warning" style="display: none;">
            <strong>⚠️ Peringatan!</strong> Deadline tidak boleh kurang dari tanggal hari ini.
        </div>
        
        <form action="" method="post" class="border rounded bg-dark p-4">
            <input type="hidden" name="id" value="<?php echo $task['id']; ?>">

            <label class="form-label">📝 Nama Task</label>
            <input type="text" name="task" class="form-control" value="<?php echo htmlspecialchars($task['task']); ?>" required>
            
            <label class="form-label mt-2">🚦 Prioritas</label>
            <select name="priority" class="form-select" required>
                <option value="Low" <?php if ($task['priority'] == "Low") echo 'selected'; ?>>Low</option>
                <option value="Medium" <?php if ($task['priority'] == "Medium") echo 'selected'; ?>>Medium</option>
                <option value="High" <?php if ($task['priority'] == "High") echo 'selected'; ?>>High</option>
            </select>
            
            <label class="form-label mt-2">📅 Tanggal</label>
            <input type="date" name="due_date" class="form-control" value="<?php echo htmlspecialchars($task['due_date']); ?>" min="<?php echo date('Y-m-d'); ?>" required> 

            <label class="form-label mt-2">🔄 Status</label>
            <select name="status" class="form-select" required>
                <option value="0" <?php if ($task['status'] == "0") echo 'selected'; ?>>Belum Selesai</option>
                <option value="1" <?php if ($task['status'] == "1") echo 'selected'; ?>>Selesai</option>
            </select>

            <button class="btn btn-primary w-100 mt-3" name="update_task">✅ Update</button>
            <a href="index.php" class="btn btn-secondary w-100 mt-2">🔙 Kembali</a>
        </form>
    </div>

    <!-- Validation Modal -->
    <div class="modal fade" id="validationModal" tabindex="-1" aria-labelledby="validationModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="validationModalLabel">⚠️ Peringatan</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    Tanggal deadline tidak boleh kurang dari tanggal hari ini!
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Tutup</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
        // Date validation script
        document.addEventListener('DOMContentLoaded', function() {
            const dueDateInput = document.querySelector('input[name="due_date"]');
            const form = document.querySelector('form');
            const today = new Date().toISOString().split('T')[0];
            
            // Check if the current due date is in the past
            if (dueDateInput.value < today) {
                // Show modal automatically if the current date is in the past
                const validationModal = new bootstrap.Modal(document.getElementById('validationModal'));
                validationModal.show();
            }
            
            form.addEventListener('submit', function(event) {
                if (dueDateInput.value < today) {
                    event.preventDefault();
                    const validationModal = new bootstrap.Modal(document.getElementById('validationModal'));
                    validationModal.show();
                }
            });
        });
    </script>
</body>
</html>