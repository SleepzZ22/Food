<?php
require_once 'db.php';

$id = $_GET['id'] ?? null;
$food = ['name_th' => '', 'category' => '', 'image' => ''];
$recipes = [];

// กรณี "แก้ไข" -> ดึงข้อมูลเดิมมาเติมลงในฟอร์ม
if ($id) {
    $stmt = $pdo->prepare("SELECT * FROM foods WHERE id = ?");
    $stmt->execute([$id]);
    $food = $stmt->fetch();
    
    if (!$food) {
        header("Location: index.php");
        exit;
    }

    $stmtR = $pdo->prepare("SELECT * FROM recipes WHERE food_id = ?");
    $stmtR->execute([$id]);
    $recipes = $stmtR->fetchAll();
}

// เมื่อผู้ใช้กดปุ่ม "บันทึกข้อมูล"
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name_th = trim($_POST['name_th'] ?? '');
    $category = trim($_POST['category'] ?? '');
    $imageName = $food['image']; // ใช้รูปเดิมเป็นค่าเริ่มต้น

    // จัดการการอัปโหลดไฟล์รูปภาพ
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $fileTmpPath = $_FILES['image']['tmp_name'];
        $fileName = $_FILES['image']['name'];
        $fileExtension = strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
        
        // อนุญาตให้อัปโหลดเฉพาะนามสกุลรูปภาพ
        $allowedExtensions = ['jpg', 'jpeg', 'png', 'webp'];
        
        if (in_array($fileExtension, $allowedExtensions)) {
            // ตั้งชื่อไฟล์ใหม่ด้วย microtime ป้องกันชื่อซ้ำกัน
            $newFileName = md5(time() . $fileName) . '.' . $fileExtension;
            $uploadFileDir = 'uploads/';
            
            if(!is_dir($uploadFileDir)) {
                mkdir($uploadFileDir, 0777, true);
            }
            
            $dest_path = $uploadFileDir . $newFileName;
            
            if(move_uploaded_file($fileTmpPath, $dest_path)) {
                // ถ้ามีรูปเก่าอยู่แล้ว ให้ลบทิ้งเพื่อประหยัดพื้นที่เซิร์ฟเวอร์
                if (!empty($food['image']) && file_exists($uploadFileDir . $food['image'])) {
                    unlink($uploadFileDir . $food['image']);
                }
                $imageName = $newFileName;
            }
        }
    }

    if ($id) {
        // --- กรณีแก้ไข (Update) ---
        $stmt = $pdo->prepare("UPDATE foods SET name_th = ?, category = ?, image = ? WHERE id = ?");
        $stmt->execute([$name_th, $category, $imageName, $id]);

        // ลบวัตถุดิบเดิมออกแล้วบันทึกใหม่ยกเซ็ต
        $pdo->prepare("DELETE FROM recipes WHERE food_id = ?")->execute([$id]);
        $food_id = $id;
    } else {
        // --- กรณีเพิ่มใหม่ (Create) ---
        $stmt = $pdo->prepare("INSERT INTO foods (name_th, category, image) VALUES (?, ?, ?)");
        $stmt->execute([$name_th, $category, $imageName]);
        $food_id = $pdo->lastInsertId();
    }

    // บันทึกรายการวัตถุดิบ (Recipes List)
    if (isset($_POST['recipes']) && is_array($_POST['recipes'])) {
        $stmtRecipe = $pdo->prepare("INSERT INTO recipes (food_id, recipe_name, quantity, unit_name) VALUES (?, ?, ?, ?)");
        foreach ($_POST['recipes'] as $r) {
            $rName = trim($r['recipe_name'] ?? '');
            $rQty = trim($r['quantity'] ?? '');
            $rUnit = trim($r['unit_name'] ?? '');

            if ($rName !== '' && $rQty !== '') {
                $stmtRecipe->execute([$food_id, $rName, $rQty, $rUnit]);
            }
        }
    }

    header("Location: index.php");
    exit;
}
?>
<!DOCTYPE html>
<html lang="th">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $id ? 'แก้ไขข้อมูล' : 'เพิ่มข้อมูล' ?>เมนูอาหาร | Food & Recipe</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <!-- Custom CSS (Sharp Edge Design) -->
    <link href="style.css" rel="stylesheet">
</head>
<body>
    <!-- Top Header -->
    <header class="app-header">
        <div class="container d-flex justify-content-between align-items-center">
            <div>
                <span class="brand-badge"><i class="bi bi-pencil-square me-1"></i> RECIPE EDITOR</span>
                <h1 class="brand-title"><?= $id ? 'แก้ไขข้อมูล<span>เมนูอาหาร</span>' : 'เพิ่มข้อมูล<span>เมนูอาหารใหม่</span>' ?></h1>
            </div>
            <div>
                <a href="index.php" class="btn btn-outline-dark">
                    <i class="bi bi-arrow-left"></i> กลับสู่หน้ารายการ
                </a>
            </div>
        </div>
    </header>

    <main class="container my-4" style="max-width: 860px;">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h4 class="fs-5 mb-0">
                    <i class="bi <?= $id ? 'bi-pencil-fill' : 'bi-plus-circle-fill' ?> me-2 text-warning"></i>
                    <?= $id ? 'แบบฟอร์มแก้ไขข้อมูลเมนูอาหาร #' . str_pad($id, 4, '0', STR_PAD_LEFT) : 'แบบฟอร์มเพิ่มเมนูอาหารใหม่' ?>
                </h4>
                <span class="badge bg-light text-dark fw-bold border">SHARP UI</span>
            </div>
            <div class="card-body p-4 p-md-5">
                <form action="" method="POST" enctype="multipart/form-data" id="foodForm">
                    
                    <!-- ส่วนที่ 1: ข้อมูลอาหารหลัก -->
                    <div class="d-flex align-items-center gap-2 border-bottom border-2 border-dark pb-2 mb-4">
                        <i class="bi bi-info-square-fill text-dark fs-5"></i>
                        <h5 class="mb-0 fw-bold">1. ข้อมูลทั่วไปของเมนู</h5>
                    </div>

                    <div class="row g-3 mb-4">
                        <div class="col-md-7">
                            <label class="form-label">ชื่ออาหาร (ภาษาไทย) <span class="text-danger">*</span></label>
                            <input type="text" name="name_th" class="form-control" value="<?= htmlspecialchars($food['name_th']) ?>" required placeholder="เช่น ต้มยำกุ้งน้ำข้น, ข้าวเหนียวมะม่วง">
                        </div>
                        <div class="col-md-5">
                            <label class="form-label">หมวดหมู่ <span class="text-danger">*</span></label>
                            <select name="category" class="form-select" required>
                                <option value="">-- เลือกหมวดหมู่ --</option>
                                <option value="อาหารคาว" <?= $food['category'] == 'อาหารคาว' ? 'selected' : '' ?>>🍲 อาหารคาว</option>
                                <option value="อาหารหวาน" <?= $food['category'] == 'อาหารหวาน' ? 'selected' : '' ?>>🍰 อาหารหวาน</option>
                                <option value="เครื่องดื่ม" <?= $food['category'] == 'เครื่องดื่ม' ? 'selected' : '' ?>>🥤 เครื่องดื่ม</option>
                            </select>
                        </div>
                    </div>

                    <!-- ส่วนอัปโหลดรูปภาพ -->
                    <div class="mb-5">
                        <label class="form-label d-block">รูปภาพอาหาร</label>
                        <div class="upload-dropzone" onclick="document.getElementById('imageInput').click();">
                            <i class="bi bi-cloud-arrow-up fs-2 text-dark d-block mb-2"></i>
                            <div class="fw-bold text-dark">คลิกเพื่อเลือกรูปภาพ หรือ ลากไฟล์มาวางที่นี่</div>
                            <div class="text-muted small mt-1">รองรับไฟล์: JPG, JPEG, PNG, WEBP (ขอบตัดตรง 1:1)</div>
                            <input type="file" name="image" class="d-none" accept="image/jpeg, image/png, image/webp" id="imageInput" onchange="previewImage(event)">
                        </div>

                        <!-- ตัวอย่างรูปภาพ (Sharp Preview Box) -->
                        <div id="previewContainer" class="<?= (!empty($food['image']) && file_exists('uploads/' . $food['image'])) ? '' : 'd-none' ?> mt-3">
                            <div class="text-secondary small fw-bold mb-1">ภาพปัจจุบัน / ภาพตัวอย่าง:</div>
                            <div class="preview-box">
                                <img id="preview" 
                                     src="<?= (!empty($food['image']) && file_exists('uploads/' . $food['image'])) ? 'uploads/' . htmlspecialchars($food['image']) : '#' ?>" 
                                     alt="ตัวอย่างรูปภาพ">
                            </div>
                        </div>
                    </div>

                    <!-- ส่วนที่ 2: ข้อมูลสูตรและวัตถุดิบ -->
                    <div class="d-flex justify-content-between align-items-center border-bottom border-2 border-dark pb-2 mb-4">
                        <div class="d-flex align-items-center gap-2">
                            <i class="bi bi-list-columns-reverse text-dark fs-5"></i>
                            <h5 class="mb-0 fw-bold">2. สูตรและสัดส่วนวัตถุดิบ (Recipe)</h5>
                        </div>
                        <button type="button" class="btn btn-outline-dark btn-sm" id="add-recipe-btn">
                            <i class="bi bi-plus-lg"></i> เพิ่มแถววัตถุดิบ
                        </button>
                    </div>

                    <div id="recipe-container">
                        <!-- หัวคอลัมน์ของรายการวัตถุดิบ -->
                        <div class="row g-2 mb-2 text-secondary fw-bold small d-none d-md-flex px-1">
                            <div class="col-5">ชื่อวัตถุดิบ</div>
                            <div class="col-3">ปริมาณ</div>
                            <div class="col-3">หน่วยนับ</div>
                            <div class="col-1 text-center">ลบ</div>
                        </div>

                        <?php if (empty($recipes)): ?>
                            <!-- แถวเริ่มต้นกรณีเพิ่มข้อมูลใหม่ -->
                            <div class="row g-2 recipe-row align-items-center">
                                <div class="col-12 col-md-5">
                                    <input type="text" name="recipes[0][recipe_name]" class="form-control" placeholder="ชื่อวัตถุดิบ เช่น กุ้งแม่น้ำ">
                                </div>
                                <div class="col-6 col-md-3">
                                    <input type="number" step="any" name="recipes[0][quantity]" class="form-control" placeholder="ปริมาณ เช่น 300">
                                </div>
                                <div class="col-6 col-md-3">
                                    <input type="text" name="recipes[0][unit_name]" class="form-control" placeholder="หน่วย เช่น กรัม">
                                </div>
                                <div class="col-12 col-md-1 text-end">
                                    <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeRow(this)" title="ลบแถวนี้">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                            </div>
                        <?php else: ?>
                            <!-- แสดงวัตถุดิบเดิมกรณีแก้ไข -->
                            <?php foreach ($recipes as $index => $r): ?>
                                <div class="row g-2 recipe-row align-items-center">
                                    <div class="col-12 col-md-5">
                                        <input type="text" name="recipes[<?= $index ?>][recipe_name]" class="form-control" value="<?= htmlspecialchars($r['recipe_name']) ?>" required placeholder="ชื่อวัตถุดิบ">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <input type="number" step="any" name="recipes[<?= $index ?>][quantity]" class="form-control" value="<?= $r['quantity'] + 0 ?>" required placeholder="ปริมาณ">
                                    </div>
                                    <div class="col-6 col-md-3">
                                        <input type="text" name="recipes[<?= $index ?>][unit_name]" class="form-control" value="<?= htmlspecialchars($r['unit_name']) ?>" required placeholder="หน่วย">
                                    </div>
                                    <div class="col-12 col-md-1 text-end">
                                        <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeRow(this)" title="ลบแถวนี้">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </div>

                    <!-- ปุ่มควบคุมการบันทึก -->
                    <div class="mt-5 pt-3 border-top border-2 border-dark d-flex justify-content-between align-items-center">
                        <a href="index.php" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> ยกเลิก
                        </a>
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-floppy-fill"></i> <?= $id ? 'บันทึกการแก้ไข' : 'บันทึกเมนูใหม่' ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </main>

    <!-- JavaScript สำหรับ Preview รูปภาพ และจัดการแถววัตถุดิบ -->
    <script>
        function previewImage(event) {
            const input = event.target;
            const preview = document.getElementById('preview');
            const previewContainer = document.getElementById('previewContainer');

            if (input.files && input.files[0]) {
                const reader = new FileReader();
                reader.onload = function(e) {
                    preview.src = e.target.result;
                    previewContainer.classList.remove('d-none');
                }
                reader.readAsDataURL(input.files[0]);
            }
        }

        function removeRow(btn) {
            const container = document.getElementById('recipe-container');
            const rows = container.querySelectorAll('.recipe-row');
            if (rows.length > 1) {
                btn.closest('.recipe-row').remove();
            } else {
                // ถ้าเหลือแถวสุดท้าย ให้เคลียร์ค่าแทนการลบ
                const inputs = btn.closest('.recipe-row').querySelectorAll('input');
                inputs.forEach(input => input.value = '');
            }
        }

        let recipeIndex = <?= max(count($recipes), 1) ?>;
        
        document.getElementById('add-recipe-btn').addEventListener('click', function() {
            const container = document.getElementById('recipe-container');
            const newRow = document.createElement('div');
            newRow.className = 'row g-2 recipe-row align-items-center';
            newRow.innerHTML = `
                <div class="col-12 col-md-5">
                    <input type="text" name="recipes[${recipeIndex}][recipe_name]" class="form-control" placeholder="ชื่อวัตถุดิบ" required>
                </div>
                <div class="col-6 col-md-3">
                    <input type="number" step="any" name="recipes[${recipeIndex}][quantity]" class="form-control" placeholder="ปริมาณ" required>
                </div>
                <div class="col-6 col-md-3">
                    <input type="text" name="recipes[${recipeIndex}][unit_name]" class="form-control" placeholder="หน่วย เช่น ช้อนโต๊ะ, กรัม" required>
                </div>
                <div class="col-12 col-md-1 text-end">
                    <button type="button" class="btn btn-danger btn-sm w-100" onclick="removeRow(this)" title="ลบแถวนี้">
                        <i class="bi bi-x-lg"></i>
                    </button>
                </div>
            `;
            container.appendChild(newRow);
            recipeIndex++;
        });
    </script>
</body>
</html>