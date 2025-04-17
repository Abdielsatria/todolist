<?php
$koneksi = mysqli_connect("localhost", "root", "pacarjaemin", "todo_list");

if (isset($_POST['add_task'])) {
    $task = $_POST['task'];
    $priority = $_POST['priority'];
    $due_date = $_POST['due_date'];
    $current_date = date('Y-m-d');

    // Validasi tanggal deadline
    if ($due_date < $current_date) {
        echo "<script>alert('Deadline tidak boleh kurang dari tanggal hari ini!'); window.location='index.php';</script>";
        exit();
    }

    if (!empty($task) && !empty($priority) && !empty($due_date)) {
        $stmt = mysqli_prepare($koneksi, "INSERT INTO task (task, priority, due_date, status) VALUES (?, ?, ?, '0')");
        mysqli_stmt_bind_param($stmt, "sss", $task, $priority, $due_date);
        mysqli_stmt_execute($stmt);
        echo "<script>alert('Task berhasil ditambahkan'); window.location='index.php';</script>";
        exit();
    } else {
        echo "<script>alert('Task gagal ditambahkan'); window.location='index.php';</script>";
        exit();
    }
}

if (isset($_GET['toggle_status'])) {
    $id = $_GET['toggle_status'];
    $current_status = $_GET['current_status'];
    $new_status = $current_status == '0' ? '1' : '0';
    
    mysqli_query($koneksi, "UPDATE task SET status = '$new_status' WHERE id = '$id'");
    $status_text = $new_status == '1' ? 'diselesaikan' : 'dikembalikan ke belum selesai';
    echo "<script>alert('Status task berhasil $status_text'); window.location='index.php';</script>";
    exit();
}

if (isset($_GET['delete'])) {
    $id = $_GET['delete'];
    mysqli_query($koneksi, "DELETE FROM task WHERE id = '$id'");
    echo "<script>alert('Task berhasil dihapus'); window.location='index.php';</script>";
    exit();
}


// Pencarian dan filter prioritas
$search = isset($_GET['search']) ? $_GET['search'] : "";
$filter_priority = isset($_GET['priority']) ? $_GET['priority'] : "";

// Pagination
$limit = 10; // Jumlah task per halaman
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$start = ($page - 1) * $limit;

// Build query
$query_count = "SELECT COUNT(*) as total FROM task WHERE 1";
$query = "SELECT * FROM task WHERE 1";

if (!empty($search)) {
    $search_escaped = mysqli_real_escape_string($koneksi, $search);
    $query .= " AND task LIKE '%$search_escaped%'";
    $query_count .= " AND task LIKE '%$search_escaped%'";
}

if (!empty($filter_priority)) {
    $priority_escaped = mysqli_real_escape_string($koneksi, $filter_priority);
    $query .= " AND priority = '$priority_escaped'";
    $query_count .= " AND priority = '$priority_escaped'";
}

$query .= " ORDER BY status ASC, 
    CASE 
        WHEN priority = 'High' THEN 1
        WHEN priority = 'Medium' THEN 2
        WHEN priority = 'Low' THEN 3
        ELSE 4
    END, due_date ASC";

// Get total records
$result_count = mysqli_query($koneksi, $query_count);
$row_count = mysqli_fetch_assoc($result_count);
$total_records = $row_count['total'];
$total_pages = ceil($total_records / $limit);

// Add LIMIT clause for pagination
$query .= " LIMIT $start, $limit";

$result = mysqli_query($koneksi, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aplikasi To-Do List | UKK 2025</title>
    
    <!-- Bootstrap & Font -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">

    <!-- Custom CSS -->
    <style>
        body {
            background: linear-gradient(135deg, #1f1f1f, #3a3a3a);
            color: #fff;
            font-family: 'Poppins', sans-serif;
        }
        .container {
            max-width: 1200px;
        }
        .glassmorphism {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(10px);
            border-radius: 15px;
            padding: 20px;
            box-shadow: 0px 4px 10px rgba(255, 255, 255, 0.1);
        }
        .btn-custom {
            background: linear-gradient(45deg, #d4af37, #b8860b);
            border: none;
            color: #fff;
            font-weight: bold;
            transition: 0.3s;
            border-radius: 8px;
        }
        .btn-custom:hover {
            background: linear-gradient(45deg, #b8860b, #d4af37);
            transform: scale(1.05);
        }
        .input-group input {
            background: rgba(255, 255, 255, 0.1);
            border: none;
            color: #fff;
        }
        .table-dark {
            background: rgba(255, 255, 255, 0.1);
            backdrop-filter: blur(5px);
            border-radius: 10px;
        }
        .badge {
            padding: 5px 10px;
            border-radius: 8px;
            font-weight: 600;
        }
        .badge-low { background-color: #28a745; }
        .badge-medium { background-color: #ffc107; color: #000; }
        .badge-high { background-color: #dc3545; }
        .badge-belum { background-color: #ff4d4d; }
        .badge-selesai { background-color: #28a745; }
        .pagination .page-link {
            color: #fff;
            background: rgba(255, 255, 255, 0.1);
            border: none;
        }
        .pagination .page-link:hover {
            background: rgba(255, 255, 255, 0.3);
        }
        .action-btns a {
            margin-right: 4px;
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
    <div class="container mt-4">
        <h2 class="text-center mb-4">Bissmilah Lancar</h2>

        <!-- Form Tambah Task -->
        <div class="task-card">
            <form action="" method="post" id="addTaskForm">
                <label class="form-label">📝 Nama Task</label>
                <input type="text" name="task" class="form-control" placeholder="Masukan Task Baru" autocomplete="off" required>
                
                <label class="form-label mt-2">🚦 Prioritas</label>
                <select name="priority" class="form-control" required>
                    <option value="">--Pilih Prioritas--</option>
                    <option value="Low">Low</option>
                    <option value="Medium">Medium</option>
                    <option value="High">High</option>
                </select>   
                
                <label class="form-label mt-2">📅 Tanggal</label>
                <input type="date" name="due_date" class="form-control" value="<?php echo date('Y-m-d'); ?>" min="<?php echo date('Y-m-d'); ?>" required>
                
                <button class="btn btn-primary w-100 mt-3 btn-custom" name="add_task">➕ Tambah Task</button>
            </form>
        </div>

        <br>

        <!-- Form Pencarian -->
        <form method="GET">
            <div class="input-group mb-3">
                <input type="text" name="search" class="form-control" placeholder="Cari Task..." value="<?php echo htmlspecialchars($search); ?>">
                <?php if (!empty($filter_priority)): ?>
                <input type="hidden" name="priority" value="<?php echo htmlspecialchars($filter_priority); ?>">
                <?php endif; ?>
                <button class="btn btn-light" type="submit">🔍 Cari</button>
            </div>
        </form>

        <!-- Filter Prioritas -->
        <div class="d-flex justify-content-center gap-2">
            <a href="index.php<?php echo !empty($search) ? '?search='.urlencode($search) : ''; ?>" class="btn btn-secondary btn-sm">Semua</a>
            <a href="index.php?priority=High<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="btn btn-danger btn-sm">🔥 High</a>
            <a href="index.php?priority=Medium<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="btn btn-warning btn-sm">⚠️ Medium</a>
            <a href="index.php?priority=Low<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?>" class="btn btn-success btn-sm">✅ Low</a>
        </div>

        <br>

        <!-- Daftar Task -->
        <div class="task-card">
            <table class="table table-dark table-striped table-hover">
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Task</th>
                        <th>Prioritas</th>
                        <th>Tanggal</th>
                        <th>Status</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if (mysqli_num_rows($result) > 0) {
                        $no = $start + 1;
                        while ($row = mysqli_fetch_assoc($result)) {
                    ?>
                    <tr>
                        <td><?php echo $no++ ?></td>
                        <td><?php echo htmlspecialchars($row['task']) ?></td>
                        <td>
                            <?php 
                                $badgeClass = ($row['priority'] == "High") ? "badge-high" :
                                              ($row['priority'] == "Medium" ? "badge-medium" : "badge-low");
                                echo "<span class='badge $badgeClass'>{$row['priority']}</span>";
                            ?>
                        </td>
                        <td><?php echo $row['due_date']?></td>
                        <td>
                            <span class="badge <?php echo ($row['status'] == 0) ? 'badge-belum' : 'badge-selesai'; ?>">
                                <?php echo ($row['status'] == 0) ? 'Belum Selesai' : 'Selesai'; ?>
                            </span>
                        </td>
                        <td class="action-btns">
                            <?php if ($row['status'] == 0): ?>
                                <a href="?toggle_status=<?php echo $row['id'] ?>&current_status=0" class="btn btn-success btn-sm" title="Tandai Selesai"><i class="fas fa-check"></i></a>
                            <?php else: ?>
                                <a href="?toggle_status=<?php echo $row['id'] ?>&current_status=1" class="btn btn-warning btn-sm" title="Tandai Belum Selesai"><i class="fas fa-undo"></i></a>
                            <?php endif; ?>
                            <a href="edit.php?id=<?php echo $row['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                            <a href="?delete=<?php echo $row['id'] ?>" class="btn btn-danger btn-sm"><i class="fas fa-trash"></i></a>
                        </td>
                    </tr>
                    <?php
                        }
                    } else {
                        echo "<tr><td colspan='6' class='text-center'>Belum ada task</td></tr>";
                    }
                    ?>
                </tbody>
            </table>

            <!-- Pagination -->
            <?php if ($total_records > 0): ?>
            <div class="pagination-info">
                Menampilkan <?php echo $start + 1 ?> - <?php echo min($start + $limit, $total_records) ?> dari <?php echo $total_records ?> task
            </div>
            <?php endif; ?>
            
            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination">
                    <?php if ($page > 1): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=1<?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($filter_priority) ? '&priority='.urlencode($filter_priority) : ''; ?>" aria-label="First">
                            <span aria-hidden="true">&laquo;&laquo;</span>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page - 1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($filter_priority) ? '&priority='.urlencode($filter_priority) : ''; ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                    
                    <?php
                    // Calculate range of pages to display
                    $range = 2; // Number of pages before and after current page
                    $initial_page = max(1, $page - $range);
                    $final_page = min($total_pages, $page + $range);
                    
                    // Always show first page
                    if ($initial_page > 1) {
                        echo '<li class="page-item"><a class="page-link" href="?page=1'.(!empty($search) ? '&search='.urlencode($search) : '').(!empty($filter_priority) ? '&priority='.urlencode($filter_priority) : '').'">1</a></li>';
                        if ($initial_page > 2) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                    }
                    
                    // Display page links
                    for ($i = $initial_page; $i <= $final_page; $i++) {
                        echo '<li class="page-item '.($i == $page ? 'active' : '').'">
                            <a class="page-link" href="?page='.$i.(!empty($search) ? '&search='.urlencode($search) : '').(!empty($filter_priority) ? '&priority='.urlencode($filter_priority) : '').'">'.$i.'</a>
                        </li>';
                    }
                    
                    // Always show last page
                    if ($final_page < $total_pages) {
                        if ($final_page < $total_pages - 1) {
                            echo '<li class="page-item disabled"><span class="page-link">...</span></li>';
                        }
                        echo '<li class="page-item"><a class="page-link" href="?page='.$total_pages.(!empty($search) ? '&search='.urlencode($search) : '').(!empty($filter_priority) ? '&priority='.urlencode($filter_priority) : '').'">'.$total_pages.'</a></li>';
                    }
                    ?>
                    
                    <?php if ($page < $total_pages): ?>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $page + 1; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($filter_priority) ? '&priority='.urlencode($filter_priority) : ''; ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                    <li class="page-item">
                        <a class="page-link" href="?page=<?php echo $total_pages; ?><?php echo !empty($search) ? '&search='.urlencode($search) : ''; ?><?php echo !empty($filter_priority) ? '&priority='.urlencode($filter_priority) : ''; ?>" aria-label="Last">
                            <span aria-hidden="true">&raquo;&raquo;</span>
                        </a>
                    </li>
                    <?php endif; ?>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>