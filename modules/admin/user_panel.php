<!-- modules/admin/user_panel.php -->
<div class="user-panel">
    <div class="user-info">
        <span class="user-name">
            <i class="fas fa-user"></i>
            <!-- <?php echo htmlspecialchars($currentUser['username'] ?? ''); ?> -->
            <?php echo htmlspecialchars($currentUser['first_name'] ?? ''); ?>
            <?php echo htmlspecialchars($currentUser['last_name'] ?? ''); ?>
            <!-- <span class="user-info-text">(ID: <?php echo $currentUser['id']; ?>)</span> -->
        </span>
        <a href="/modules/auth/logout.php" class="logout-btn">
          Выйти <i class="fas fa-sign-out-alt"></i>
        </a>
    </div>
</div>

<style>
  /* Стили для панели пользователя */
.user-panel {
    background-color: #f8f9fa;
    padding: 5px 10px;
    border-bottom: 1px solid #dee2e6;
    margin-bottom: 0px;
}

.user-info {
    max-width: 1200px;
    margin: 0 auto;
    display: flex;
    justify-content: space-between;
    align-items: center;
    /* gap: 15px; */
}

.user-name {
    font-weight: bold;
    color: #495057;
}

.user-name i {
    /* margin-right: 5px; */
    color: #007bff;
}

.logout-btn {
    color: #dc3545;
    text-decoration: none;
    /* padding: 5px 10px; */
    border-radius: 4px;
    transition: background-color 0.3s;
}

.logout-btn:hover {
    background-color: #f8d7da;
}

.logout-btn i {
    margin-right: 5px;
}
</style>