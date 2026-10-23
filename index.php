<?php
require_once 'db.php';

// จัดการการลบข้อมูล (Delete) รวมถึงลบไฟล์รูปภาพออกจากโฟลเดอร์เพื่อไม่ให้เปลืองพื้นที่
if (isset($_GET['delete_id'])) {
    $stmt = $pdo->prepare("SELECT image FROM foods WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    $foodItem = $stmt->fetch();
    
    if ($foodItem && !empty($foodItem['image'])) {
        $imagePath = 'uploads/' . $foodItem['image'];
        if (file_exists($imagePath)) {
            unlink($imagePath); // ลบไฟล์รูปจริงออกจากเซิร์ฟเวอร์
        }
    }

    $stmt = $pdo->prepare("DELETE FROM foods WHERE id = ?");
    $stmt->execute([$_GET['delete_id']]);
    header("Location: index.php");
    exit;
}

// ดึงข้อมูลอาหารทั้งหมดมาแสดงผล (Read)
$stmt = $pdo->query("SELECT * FROM foods ORDER BY id DESC");
$foods = $stmt->fetchAll();

// ดึงวัตถุดิบทั้งหมดจัดกลุ่มตาม food_id เพื่อประสิทธิภาพ
$recipesByFood = [];
$stmtRecipes = $pdo->query("SELECT * FROM recipes ORDER BY id ASC");
while ($r = $stmtRecipes->fetch()) {
    $recipesByFood[$r['food_id']][] = $r;
}

// นับจำนวนแยกตามหมวดหมู่
$totalCount = count($foods);
$savoryCount = 0;
$sweetCount = 0;
$drinkCount = 0;
foreach ($foods as $f) {
    if ($f['category'] === 'อาหารคาว') $savoryCount++;
    elseif ($f['category'] === 'อาหารหวาน') $sweetCount++;
    elseif ($f['category'] === 'เครื่องดื่ม') $drinkCount++;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ระบบจัดการข้อมูลอาหารและสูตรอาหาร | Food & Recipe</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS (Sharp Edge Design) -->
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <!-- Top App Bar / Brand Header -->
    <header class="app-header">
        <div class="container d-flex flex-wrap justify-content-between align-items-center gap-3">
            <div>
                <span class="brand-badge"><i class="bi bi-fire me-1"></i> FOOD & RECIPE SYSTEM</span>
                <h1 class="brand-title">ระบบจัดการ<span>ข้อมูลอาหาร</span>และสูตรอาหาร</h1>
            </div>
            <div>
                <a href="manage.php" class="btn btn-primary">
                    <i class="bi bi-plus-lg"></i> เพิ่มเมนูอาหารใหม่
                </a>
            </div>
        </div>
    </header>

    <main class="container">
        <!-- Overview Stats Chips -->
        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3">
                <div class="stat-pill w-100">
                    <i class="bi bi-grid text-muted fs-4"></i>
                    <div>
                        <div class="stat-num"><?= $totalCount ?></div>
                        <div class="stat-label">เมนูทั้งหมด</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-pill w-100" style="border-left: 4px solid var(--cat-savory-text);">
                    <i class="bi bi-egg-fried fs-4 text-warning"></i>
                    <div>
                        <div class="stat-num"><?= $savoryCount ?></div>
                        <div class="stat-label">อาหารคาว</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-pill w-100" style="border-left: 4px solid var(--cat-sweet-text);">
                    <i class="bi bi-cake2 fs-4 text-danger"></i>
                    <div>
                        <div class="stat-num"><?= $sweetCount ?></div>
                        <div class="stat-label">อาหารหวาน</div>
                    </div>
                </div>
            </div>
            <div class="col-6 col-md-3">
                <div class="stat-pill w-100" style="border-left: 4px solid var(--cat-drink-text);">
                    <i class="bi bi-cup-straw fs-4 text-success"></i>
                    <div>
                        <div class="stat-num"><?= $drinkCount ?></div>
                        <div class="stat-label">เครื่องดื่ม</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Filter & Search Toolbar -->
        <div class="filter-container d-flex flex-wrap align-items-center justify-content-between gap-3">
            <div class="d-flex flex-wrap align-items-center gap-2">
                <span class="text-secondary small fw-bold text-uppercase me-1"><i class="bi bi-funnel"></i> หมวดหมู่:</span>
                <button type="button" class="filter-btn active" data-category="all">ทั้งหมด (<?= $totalCount ?>)</button>
                <button type="button" class="filter-btn" data-category="อาหารคาว">อาหารคาว (<?= $savoryCount ?>)</button>
                <button type="button" class="filter-btn" data-category="อาหารหวาน">อาหารหวาน (<?= $sweetCount ?>)</button>
                <button type="button" class="filter-btn" data-category="เครื่องดื่ม">เครื่องดื่ม (<?= $drinkCount ?>)</button>
            </div>
            <div style="min-width: 260px;">
                <div class="input-group">
                    <span class="input-group-text bg-white border-dark text-muted"><i class="bi bi-search"></i></span>
                    <input type="text" id="searchInput" class="form-control" placeholder="ค้นหาชื่ออาหาร หรือ วัตถุดิบ...">
                </div>
            </div>
        </div>

        <!-- Table Container -->
        <div class="table-container mb-5">
            <table class="table align-middle" id="foodTable">
                <thead>
                    <tr>
                        <th style="width: 10%;" class="text-center">รูปภาพ</th>
                        <th style="width: 22%;">ชื่ออาหาร (ไทย)</th>
                        <th style="width: 15%;">หมวดหมู่</th>
                        <th style="width: 38%;">สูตรและวัตถุดิบ (Recipe)</th>
                        <th style="width: 15%;" class="text-center">จัดการ</th>
                    </tr>
                </thead>
                <tbody id="foodTableBody">
                    <?php if (empty($foods)): ?>
                        <tr id="noDataRow">
                            <td colspan="5" class="p-0">
                                <div class="empty-state">
                                    <div class="empty-state-icon">🍲</div>
                                    <h4 class="empty-state-title">ยังไม่มีข้อมูลอาหารในระบบ</h4>
                                    <p class="empty-state-desc">เริ่มต้นสร้างฐานข้อมูลเมนูอาหาร พร้อมกำหนดสูตรและสัดส่วนวัตถุดิบได้ง่ายๆ</p>
                                    <a href="manage.php" class="btn btn-primary">
                                        <i class="bi bi-plus-lg"></i> เพิ่มเมนูอาหารแรกเลย
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($foods as $food): 
                            $recipes = $recipesByFood[$food['id']] ?? [];
                            $categoryClass = 'default';
                            if ($food['category'] === 'อาหารคาว') $categoryClass = 'savory';
                            elseif ($food['category'] === 'อาหารหวาน') $categoryClass = 'sweet';
                            elseif ($food['category'] === 'เครื่องดื่ม') $categoryClass = 'drink';
                            
                            // รวมชื่อวัตถุดิบไว้สำหรับ search filter
                            $recipeKeywords = '';
                            foreach ($recipes as $r) {
                                $recipeKeywords .= ' ' . $r['recipe_name'];
                            }
                        ?>
                            <tr class="food-row" 
                                data-category="<?= htmlspecialchars($food['category']) ?>" 
                                data-keywords="<?= htmlspecialchars(strtolower($food['name_th'] . ' ' . $food['category'] . ' ' . $recipeKeywords)) ?>">
                                <td class="text-center">
                                    <div class="food-thumb-wrap mx-auto">
                                        <?php if (!empty($food['image']) && file_exists('uploads/' . $food['image'])): ?>
                                            <img src="uploads/<?= htmlspecialchars($food['image']) ?>" alt="<?= htmlspecialchars($food['name_th']) ?>">
                                        <?php else: ?>
                                            <div class="no-image-placeholder">
                                                <i class="bi bi-card-image fs-5 text-secondary"></i><br>ไม่มีรูป
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="fw-bold fs-6 text-dark"><?= htmlspecialchars($food['name_th']) ?></div>
                                    <span class="text-muted small">#ID-<?= str_pad($food['id'], 4, '0', STR_PAD_LEFT) ?></span>
                                </td>
                                <td>
                                    <span class="cat-badge <?= $categoryClass ?>">
                                        <?= htmlspecialchars($food['category']) ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($recipes)): ?>
                                        <ul class="recipe-tags-list">
                                            <?php foreach ($recipes as $r): ?>
                                                <li class="recipe-tag">
                                                    <span><?= htmlspecialchars($r['recipe_name']) ?></span>
                                                    <span class="qty"><?= $r['quantity'] + 0 ?> <?= htmlspecialchars($r['unit_name']) ?></span>
                                                </li>
                                            <?php endforeach; ?>
                                        </ul>
                                    <?php else: ?>
                                        <span class="text-muted small fst-italic"><i class="bi bi-dash-circle me-1"></i>ยังไม่ได้ระบุวัตถุดิบ</span>
                                    <?php endif; ?>
                                </td>
                                <td class="text-center">
                                    <div class="d-inline-flex gap-1">
                                        <a href="manage.php?id=<?= $food['id'] ?>" class="btn btn-warning btn-sm" title="แก้ไข">
                                            <i class="bi bi-pencil-square"></i> แก้ไข
                                        </a>
                                        <a href="index.php?delete_id=<?= $food['id'] ?>" 
                                           class="btn btn-danger btn-sm" 
                                           onclick="return confirm('ยืนยันการลบเมนู: <?= htmlspecialchars(addslashes($food['name_th'])) ?> ?\nข้อมูลรูปภาพและสูตรอาหารที่เกี่ยวข้องจะถูกลบทั้งหมด');"
                                           title="ลบ">
                                            <i class="bi bi-trash3"></i> ลบ
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
            
            <!-- Filter No Match Message -->
            <div id="noMatchMessage" class="empty-state d-none">
                <div class="empty-state-icon">🔍</div>
                <h4 class="empty-state-title">ไม่พบเมนูอาหารที่ค้นหา</h4>
                <p class="empty-state-desc">ลองเปลี่ยนคำค้นหา หรือเลือกหมวดหมู่อื่นดูใหม่อีกครั้ง</p>
                <button type="button" class="btn btn-secondary btn-sm" onclick="resetFilters()">
                    <i class="bi bi-arrow-counterclockwise"></i> รีเซ็ตตัวกรอง
                </button>
            </div>
        </div>
    </main>

    <!-- JavaScript For Real-Time Search & Category Filter -->
    <script>
        const searchInput = document.getElementById('searchInput');
        const filterBtns = document.querySelectorAll('.filter-btn');
        const rows = document.querySelectorAll('.food-row');
        const noMatchMessage = document.getElementById('noMatchMessage');
        let currentCategory = 'all';

        function applyFilter() {
            const query = searchInput ? searchInput.value.trim().toLowerCase() : '';
            let visibleCount = 0;

            rows.forEach(row => {
                const rowCat = row.getAttribute('data-category');
                const rowKeywords = row.getAttribute('data-keywords') || '';

                const matchesCat = (currentCategory === 'all' || rowCat === currentCategory);
                const matchesQuery = (query === '' || rowKeywords.includes(query));

                if (matchesCat && matchesQuery) {
                    row.style.display = '';
                    visibleCount++;
                } else {
                    row.style.display = 'none';
                }
            });

            if (noMatchMessage) {
                if (visibleCount === 0 && rows.length > 0) {
                    noMatchMessage.classList.remove('d-none');
                } else {
                    noMatchMessage.classList.add('d-none');
                }
            }
        }

        if (searchInput) {
            searchInput.addEventListener('input', applyFilter);
        }

        filterBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                filterBtns.forEach(b => b.classList.remove('active'));
                this.classList.add('active');
                currentCategory = this.getAttribute('data-category');
                applyFilter();
            });
        });

        function resetFilters() {
            if (searchInput) searchInput.value = '';
            currentCategory = 'all';
            filterBtns.forEach(b => {
                if (b.getAttribute('data-category') === 'all') {
                    b.classList.add('active');
                } else {
                    b.classList.remove('active');
                }
            });
            applyFilter();
        }
    </script>
</body>
</html>