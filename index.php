<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "yashdb";

$alerts = [];
$action = $_POST['action'] ?? '';
$selectedUser = null;

function push_alert(&$alerts, string $text, string $type = 'info'): void
{
    $alerts[] = ['text' => $text, 'type' => $type];
}

$conn = new mysqli($servername, $username, $password, $dbname);
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}
push_alert($alerts, 'Connected to database.', 'success');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($action === 'create') {
        $name = trim($_POST['name'] ?? '');
        $email = trim($_POST['email'] ?? '');

        if ($name !== '' && $email !== '') {
            $stmt = $conn->prepare("INSERT INTO user (name, email) VALUES (?, ?)");
            $stmt->bind_param('ss', $name, $email);

            if ($stmt->execute()) {
                push_alert($alerts, "User '{$name}' added.", 'success');
            } else {
                push_alert($alerts, 'Insert failed: ' . $stmt->error, 'error');
            }

            $stmt->close();
        } else {
            push_alert($alerts, 'Name and email are required.', 'error');
        }
    } elseif ($action === 'read') {
        $id = filter_input(INPUT_POST, 'read_id', FILTER_VALIDATE_INT);

        if ($id) {
            $stmt = $conn->prepare("SELECT id, name, email FROM user WHERE id = ?");
            $stmt->bind_param('i', $id);
            $stmt->execute();
            $stmt->bind_result($uid, $uname, $uemail);
            if ($stmt->fetch()) {
                $selectedUser = ['id' => $uid, 'name' => $uname, 'email' => $uemail];
                push_alert($alerts, "Loaded user #{$uid}.", 'info');
            } else {
                push_alert($alerts, 'User not found.', 'error');
            }
            $stmt->close();
        } else {
            push_alert($alerts, 'Please provide a valid ID to read.', 'error');
        }
    } elseif ($action === 'update') {
        $id = filter_input(INPUT_POST, 'update_id', FILTER_VALIDATE_INT);
        $email = trim($_POST['update_email'] ?? '');

        if ($id && $email !== '') {
            $stmt = $conn->prepare("UPDATE user SET email = ? WHERE id = ?");
            $stmt->bind_param('si', $email, $id);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                push_alert($alerts, "User #{$id} updated.", 'success');
            } else {
                push_alert($alerts, 'No updates applied. Value may already be current or user does not exist.', 'info');
            }
            $stmt->close();
        } else {
            push_alert($alerts, 'Valid ID and new email are required for update.', 'error');
        }
    } elseif ($action === 'delete') {
        $email = trim($_POST['delete_email'] ?? '');

        if ($email !== '') {
            $stmt = $conn->prepare("DELETE FROM user WHERE email = ?");
            $stmt->bind_param('s', $email);
            $stmt->execute();
            if ($stmt->affected_rows > 0) {
                push_alert($alerts, "User with email {$email} deleted.", 'success');
            } else {
                push_alert($alerts, 'No user matched that email.', 'info');
            }
            $stmt->close();
        } else {
            push_alert($alerts, 'Email is required to delete a user.', 'error');
        }
    }
}

$users = [];
$result = $conn->query("SELECT id, name, email FROM user ORDER BY id");
if ($result) {
    while ($row = $result->fetch_assoc()) {
        $users[] = $row;
    }
}
$conn->close();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management UI</title>
    <style>
        :root {
            color-scheme: light;
            --surface: #0f172a;
            --card: #1e293b;
            --border: #334155;
            --accent: #38bdf8;
            --accent-strong: #0284c7;
            --text: #e2e8f0;
            --muted: #94a3b8;
        }

        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            font-family: "Segoe UI", system-ui, -apple-system, sans-serif;
            background: radial-gradient(circle at top, #1f2937 0%, #0b1120 65%);
            color: var(--text);
            min-height: 100vh;
            padding: 2rem;
        }

        .app-shell {
            max-width: 1200px;
            margin: 0 auto;
        }

        header {
            margin-bottom: 1.5rem;
        }

        header h1 {
            margin: 0;
            font-size: 2.25rem;
        }

        header p {
            margin: 0.4rem 0 0;
            color: var(--muted);
        }

        .message-stack {
            display: flex;
            gap: 0.75rem;
            flex-wrap: wrap;
            margin: 1rem 0 1.5rem;
        }

        .toast {
            flex: 1 1 220px;
            padding: 0.75rem 1rem;
            border-radius: 12px;
            box-shadow: 0 12px 40px rgba(15, 23, 42, 0.45);
            border: 1px solid var(--border);
            background: var(--card);
            position: relative;
            overflow: hidden;
            transition: transform 0.2s ease, opacity 0.2s ease;
        }

        .toast.fade {
            opacity: 0;
            transform: translateY(-8px);
        }

        .toast::before {
            content: '';
            position: absolute;
            inset: 0;
            background: linear-gradient(120deg, rgba(56, 189, 248, 0.25), transparent 60%);
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .toast.success::before,
        .toast.success::after {
            opacity: 1;
        }

        .toast h4 {
            margin: 0 0 0.35rem;
            font-size: 0.95rem;
        }

        .toast p {
            margin: 0;
            font-size: 0.85rem;
            color: var(--muted);
        }

        .toast.success {
            border-color: rgba(56, 189, 248, 0.4);
        }

        .toast.error {
            border-color: rgba(248, 113, 113, 0.6);
        }

        .toast.info {
            border-color: rgba(59, 130, 246, 0.6);
        }

        .main-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.25rem;
        }

        .panel {
            background: var(--card);
            border: 1px solid var(--border);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 20px 40px rgba(2, 6, 23, 0.55);
        }

        .panel h2 {
            margin-top: 0;
            margin-bottom: 0.75rem;
            font-weight: 600;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
            margin-bottom: 0.75rem;
        }

        label {
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.08em;
            color: var(--muted);
        }

        input[type="text"],
        input[type="email"],
        input[type="number"] {
            padding: 0.65rem 0.8rem;
            background: rgba(15, 23, 42, 0.65);
            border: 1px solid var(--border);
            border-radius: 10px;
            color: var(--text);
            font-size: 0.95rem;
        }

        input::placeholder {
            color: #94a3b8;
        }

        button {
            cursor: pointer;
            border: none;
            border-radius: 12px;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text);
            background: linear-gradient(135deg, var(--accent), var(--accent-strong));
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }

        button:hover {
            transform: translateY(-2px);
            box-shadow: 0 15px 35px rgba(3, 105, 161, 0.4);
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        th,
        td {
            text-align: left;
            padding: 0.85rem;
            border-bottom: 1px solid rgba(148, 163, 184, 0.2);
        }

        td {
            color: var(--text);
        }

        th {
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.08em;
            color: var(--muted);
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }

        tr.active {
            background: rgba(56, 189, 248, 0.15);
            outline: 1px solid rgba(56, 189, 248, 0.5);
        }

        .status-row {
            margin-top: 1.5rem;
            padding: 1rem;
            background: rgba(15, 23, 42, 0.75);
            border-radius: 12px;
            border: 1px dashed rgba(148, 163, 184, 0.5);
        }

        .status-row strong {
            display: block;
            margin-bottom: 0.25rem;
            color: var(--text);
        }

        @media (max-width: 640px) {
            body {
                padding: 1.25rem;
            }
        }
    </style>
</head>
<body>
    <div class="app-shell">
        <header>
            <h1>User CRUD Console</h1>
            <p>Manage users without leaving this page — add, read, edit, or remove records in real-time.</p>
        </header>
        <?php if (!empty($alerts)) : ?>
            <div class="message-stack">
                <?php foreach ($alerts as $alert) : ?>
                    <div class="toast <?= htmlspecialchars($alert['type']) ?>">
                        <h4><?= ucfirst(htmlspecialchars($alert['type'])) ?></h4>
                        <p><?= htmlspecialchars($alert['text']) ?></p>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <div class="main-grid">
            <div class="panel">
                <h2>Quick Actions</h2>
                <form id="create-user-form" method="post">
                    <input type="hidden" name="action" value="create">
                    <div class="form-group">
                        <label for="name">Name</label>
                        <input type="text" id="name" name="name" placeholder="Full name">
                    </div>
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" id="email" name="email" placeholder="name@example.com">
                    </div>
                    <button type="submit">Add user</button>
                </form>

                <form id="read-user-form" method="post" style="margin-top: 1.25rem;">
                    <input type="hidden" name="action" value="read">
                    <div class="form-group">
                        <label for="read-id">Lookup ID</label>
                        <input type="number" id="read-id" name="read_id" placeholder="e.g. 2">
                    </div>
                    <button type="submit">Fetch user</button>
                </form>

                <form id="update-user-form" method="post" style="margin-top: 1.25rem;">
                    <input type="hidden" name="action" value="update">
                    <div class="form-group">
                        <label for="update-id">ID to edit</label>
                        <input type="number" id="update-id" name="update_id" placeholder="User ID">
                    </div>
                    <div class="form-group">
                        <label for="update-email">New email</label>
                        <input type="email" id="update-email" name="update_email" placeholder="updated@example.com">
                    </div>
                    <button type="submit">Update email</button>
                </form>

                <form id="delete-user-form" method="post" style="margin-top: 1.25rem;">
                    <input type="hidden" name="action" value="delete">
                    <div class="form-group">
                        <label for="delete-email">Email to remove</label>
                        <input type="email" id="delete-email" name="delete_email" placeholder="name@example.com">
                    </div>
                    <button type="submit">Delete user</button>
                </form>
            </div>

            <div class="panel">
                <h2>Users roster</h2>
                <div class="status-row">
                    <strong>Loaded records:</strong>
                    <span><?= count($users) ?> entries</span>
                </div>
                <div style="overflow-x:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($users as $user) : ?>
                                <tr
                                    data-id="<?= htmlspecialchars($user['id']) ?>"
                                    data-name="<?= htmlspecialchars($user['name']) ?>"
                                    data-email="<?= htmlspecialchars($user['email']) ?>">
                                    <td><?= htmlspecialchars($user['id']) ?></td>
                                    <td><?= htmlspecialchars($user['name']) ?></td>
                                    <td><?= htmlspecialchars($user['email']) ?></td>
                                </tr>
                            <?php endforeach; ?>
                            <?php if (empty($users)) : ?>
                                <tr>
                                    <td colspan="3" style="text-align:center; color:var(--muted);">
                                        No users found yet.
                                    </td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>

                <?php if ($selectedUser) : ?>
                    <div class="status-row" style="margin-top: 1.5rem;">
                        <strong>Last lookup</strong>
                        <span>ID: <?= htmlspecialchars($selectedUser['id']) ?> · <?= htmlspecialchars($selectedUser['name']) ?> · <?= htmlspecialchars($selectedUser['email']) ?></span>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const rows = document.querySelectorAll('tbody tr[data-id]');
            const readIdInput = document.getElementById('read-id');
            const updateIdInput = document.getElementById('update-id');
            const updateEmailInput = document.getElementById('update-email');

            rows.forEach(row => {
                row.addEventListener('click', () => {
                    rows.forEach(r => r.classList.remove('active'));
                    row.classList.add('active');

                    const { id, email } = row.dataset;
                    readIdInput.value = id;
                    updateIdInput.value = id;
                    updateEmailInput.value = email;
                });
            });

            const toasts = document.querySelectorAll('.toast');
            toasts.forEach(toast => {
                setTimeout(() => toast.classList.add('fade'), 6000);
            });
        });
    </script>
</body>
</html>
