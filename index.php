<?php
declare(strict_types=1);
session_start();
function e(?string $value): string {
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}
function next_id(): int {
    $_SESSION['last_id'] = isset($_SESSION['last_id']) ? (int)$_SESSION['last_id'] + 1 : 3; // 1&2 reserved for seeds
    return $_SESSION['last_id'];
}
if (!isset($_SESSION['products_initialized'])) {
    $_SESSION['products'] = [
        [
            'id' => 1,
            'name' => 'Wireless Mouse',
            'description' => 'Ergonomic 2.4GHz wireless mouse with silent clicks.',
            'price' => 39.99,
            'category' => 'Electronics',
            'image' => 'https://via.placeholder.com/64x64?text=Mouse'
        ],
        [
            'id' => 2,
            'name' => 'Notebook A5',
            'description' => 'Hardcover dotted notebook for study and planning.',
            'price' => 7.50,
            'category' => 'Stationery',
            'image' => 'https://via.placeholder.com/64x64?text=A5'
        ],
    ];
    $_SESSION['last_id'] = 2;
    $_SESSION['products_initialized'] = true;
}
// by ahmed  Abosultan
$categories = ['Electronics', 'Stationery', 'Books', 'Home', 'Clothing', 'Other'];

$products = &$_SESSION['products']; 
$errors = [];
$submittedData = [];
$success = null;
$generalError = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete') {
    $delId = isset($_POST['id']) ? (int)$_POST['id'] : 0;
    $found = false;
    foreach ($products as $idx => $p) {
        if ((int)$p['id'] === $delId) {
            unset($products[$idx]);
            $products = array_values($products); // reindex
            $success = "Product #{$delId} deleted successfully.";
            $found = true;
            break;
        }
    }
    if (!$found) {
        $generalError = "Product not found or already deleted.";
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && (!isset($_POST['action']) || $_POST['action'] === 'save')) {
    // Collect & sanitize
    $submittedData = [
        'id'          => isset($_POST['id']) ? trim($_POST['id']) : '',
        'name'        => isset($_POST['name']) ? trim($_POST['name']) : '',
        'description' => isset($_POST['description']) ? trim($_POST['description']) : '',
        'price'       => isset($_POST['price']) ? trim($_POST['price']) : '',
        'category'    => isset($_POST['category']) ? trim($_POST['category']) : '',
        'image'       => isset($_POST['image']) ? trim($_POST['image']) : '',
    ];

    // Validate
    if ($submittedData['name'] === '') {
        $errors['name'] = 'Name is required.';
    } elseif (mb_strlen($submittedData['name']) > 100) {
        $errors['name'] = 'Name must be at most 100 characters.';
    }

    if ($submittedData['description'] === '') {
        $errors['description'] = 'Description is required.';
    } elseif (mb_strlen($submittedData['description']) < 10) {
        $errors['description'] = 'Description should be at least 10 characters.';
    }

    if ($submittedData['price'] === '') {
        $errors['price'] = 'Price is required.';
    } elseif (!is_numeric($submittedData['price'])) {
        $errors['price'] = 'Price must be a number.';
    } elseif ((float)$submittedData['price'] <= 0) {
        $errors['price'] = 'Price must be greater than 0.';
    } else {
        // normalize
        $submittedData['price'] = number_format((float)$submittedData['price'], 2, '.', '');
    }

    if ($submittedData['category'] === '' || !in_array($submittedData['category'], $categories, true)) {
        $errors['category'] = 'Please choose a valid category.';
    }

    if ($submittedData['image'] !== '') {
        // Basic URL validation (optional field)
        if (!filter_var($submittedData['image'], FILTER_VALIDATE_URL)) {
            $errors['image'] = 'Image must be a valid URL (or leave blank).';
        }
    }

    if (empty($errors)) {
        $isUpdate = isset($_POST['mode']) && $_POST['mode'] === 'update' && ctype_digit($submittedData['id']);
        if ($isUpdate) {
            $updateId = (int)$submittedData['id'];
            $updated = false;
            foreach ($products as &$p) {
                if ((int)$p['id'] === $updateId) {
                    $p['name'] = $submittedData['name'];
                    $p['description'] = $submittedData['description'];
                    $p['price'] = (float)$submittedData['price'];
                    $p['category'] = $submittedData['category'];
                    $p['image'] = $submittedData['image'];
                    $updated = true;
                    break;
                }
            }
            unset($p);
            if ($updated) {
                $success = "Product #{$updateId} updated successfully.";
            } else {
                $generalError = "Could not find product to update.";
            }
        } else {
            $newId = next_id();
            $products[] = [
                'id' => $newId,
                'name' => $submittedData['name'],
                'description' => $submittedData['description'],
                'price' => (float)$submittedData['price'],
                'category' => $submittedData['category'],
                'image' => $submittedData['image'],
            ];
            $success = "Product #{$newId} added successfully.";
        }

        // Clear form values after success
        if ($success) {
            $submittedData = [];
            // Also clear edit mode if any
            unset($_GET['edit_id']);
        }
    } else {
        $generalError = "Please fix the errors below and submit again.";
    }
}

$editMode = false;
$editProduct = null;

if (isset($_GET['edit_id']) && ctype_digit($_GET['edit_id'])) {
    $eid = (int)$_GET['edit_id'];
    foreach ($products as $p) {
        if ((int)$p['id'] === $eid) {
            $editProduct = $p;
            $editMode = true;
            break;
        }
    }
    if ($editMode && empty($submittedData)) {
        $submittedData = [
            'id' => (string)$editProduct['id'],
            'name' => $editProduct['name'],
            'description' => $editProduct['description'],
            'price' => number_format((float)$editProduct['price'], 2, '.', ''),
            'category' => $editProduct['category'],
            'image' => $editProduct['image'] ?? '',
        ];
    }
}

?>
<!DOCTYPE html>
<html lang="en" data-bs-theme="light">
<head>
    <meta charset="utf-8">
    <title>PHP Product by ahmed Abosultan </title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
   
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
          integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <style>
        body { padding-bottom: 4rem; }
        .table td, .table th { vertical-align: middle; }
        .product-img { width: 48px; height: 48px; object-fit: cover; border-radius: .5rem; }
    </style>
</head>
<body>
<nav class="navbar navbar-expand-lg bg-dark navbar-dark">
    <div class="container">
        <a class="navbar-brand fw-bold" href="#">PHP Product by ahmed Abosultan</a>
    </div>
</nav>

<main class="container my-4">
    <div class="row g-4">
        <div class="col-lg-7">
            <div class="d-flex align-items-center mb-3">
                <h2 class="mb-0">Products</h2>
                <span class="badge bg-secondary ms-3"><?= count($products) ?></span>
            </div>

            <?php if ($success): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?= e($success) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <?php if ($generalError && empty($success)): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?= e($generalError) ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="table-responsive">
                <table class="table table-striped align-middle">
                    <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Image</th>
                        <th>Name &amp; Category</th>
                        <th class="text-end">Price</th>
                        <th style="width: 200px;">Actions</th>
                    </tr>
                    </thead>
                    <tbody>
                    <?php if (empty($products)): ?>
                        <tr><td colspan="5" class="text-center text-muted py-4">No products yet.</td></tr>
                    <?php else: ?>
                        <?php foreach ($products as $p): ?>
                            <tr>
                                <td class="fw-semibold"><?= (int)$p['id'] ?></td>
                                <td>
                                    <?php if (!empty($p['image'])): ?>
                                        <img class="product-img" src="<?= e($p['image']) ?>" alt="img">
                                    <?php else: ?>
                                        <span class="text-muted">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="fw-semibold"><?= e($p['name']) ?></div>
                                    <div class="small text-muted"><?= e($p['category']) ?></div>
                                    <div class="small mt-1"><?= e($p['description']) ?></div>
                                </td>
                                <td class="text-end"><?= number_format((float)$p['price'], 2) ?></td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <a class="btn btn-sm btn-outline-primary"
                                           href="?edit_id=<?= (int)$p['id'] ?>">Edit</a>
                                        <!-- Delete Button triggers modal -->
                                        <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal"
                                                data-bs-target="#deleteModal" data-id="<?= (int)$p['id'] ?>">
                                            Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h4 class="card-title mb-3">
                        <?= $editMode ? 'Update Product' : 'Add New Product' ?>
                    </h4>
                    <form method="post" novalidate>
                        <input type="hidden" name="action" value="save">
                        <?php if ($editMode): ?>
                            <input type="hidden" name="mode" value="update">
                            <input type="hidden" name="id" value="<?= e($submittedData['id'] ?? '') ?>">
                        <?php endif; ?>

                        <div class="mb-3">
                            <label class="form-label" for="name">Name</label>
                            <input
                                type="text"
                                class="form-control <?= isset($errors['name']) ? 'is-invalid' : '' ?>"
                                id="name" name="name"
                                value="<?= e($submittedData['name'] ?? '') ?>"
                                maxlength="100"
                                placeholder="e.g., Bluetooth Speaker">
                            <?php if (isset($errors['name'])): ?>
                                <div class="invalid-feedback"><?= e($errors['name']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="description">Description</label>
                            <textarea
                                class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>"
                                id="description" name="description" rows="3"
                                placeholder="Brief details about the product..."><?= e($submittedData['description'] ?? '') ?></textarea>
                            <?php if (isset($errors['description'])): ?>
                                <div class="invalid-feedback"><?= e($errors['description']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="price">Price (USD)</label>
                            <input
                                type="text"
                                class="form-control <?= isset($errors['price']) ? 'is-invalid' : '' ?>"
                                id="price" name="price"
                                value="<?= e($submittedData['price'] ?? '') ?>"
                                placeholder="e.g., 19.99">
                            <?php if (isset($errors['price'])): ?>
                                <div class="invalid-feedback"><?= e($errors['price']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="category">Category</label>
                            <select
                                class="form-select <?= isset($errors['category']) ? 'is-invalid' : '' ?>"
                                id="category" name="category">
                                <option value="">— Select —</option>
                                <?php foreach ($categories as $cat): ?>
                                    <option value="<?= e($cat) ?>"
                                        <?= (isset($submittedData['category']) && $submittedData['category'] === $cat) ? 'selected' : '' ?>>
                                        <?= e($cat) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <?php if (isset($errors['category'])): ?>
                                <div class="invalid-feedback"><?= e($errors['category']) ?></div>
                            <?php endif; ?>
                        </div>

                        <!-- Optional: Image URL -->
                        <div class="mb-3">
                            <label class="form-label" for="image">Image URL (optional)</label>
                            <input
                                type="url"
                                class="form-control <?= isset($errors['image']) ? 'is-invalid' : '' ?>"
                                id="image" name="image"
                                value="<?= e($submittedData['image'] ?? '') ?>"
                                placeholder="https://example.com/photo.jpg">
                            <?php if (isset($errors['image'])): ?>
                                <div class="invalid-feedback"><?= e($errors['image']) ?></div>
                            <?php endif; ?>
                        </div>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <?= $editMode ? 'Update' : 'Add' ?>
                            </button>
                            <?php if ($editMode): ?>
                                <a href="index.php" class="btn btn-outline-secondary">Cancel</a>
                            <?php else: ?>
                                <button type="reset" class="btn btn-outline-secondary">Reset</button>
                            <?php endif; ?>
                        </div>
                    </form>
                </div>
            </div>

            <div class="small text-muted mt-3">
                * All data is stored in session for demo purposes. Refresh keeps data; closing browser may clear it.
            </div>
        </div>
    </div>
</main>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLbl" aria-hidden="true">
  <div class="modal-dialog">
    <form method="post" class="modal-content">
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="id" id="delete-id" value="">
      <div class="modal-header">
        <h5 class="modal-title" id="deleteModalLbl">Confirm delete</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        Are you sure you want to delete this product?
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
        <button type="submit" class="btn btn-danger">Yes, delete</button>
      </div>
    </form>
  </div>
</div>

<footer class="container mt-5">
    <hr>
    <p class="small text-muted">
       ❤️  PHP by Ahmed Abosultan 
    </p>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"
        integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz"
        crossorigin="anonymous"></script>
<script>

const deleteModal = document.getElementById('deleteModal');
if (deleteModal) {
    deleteModal.addEventListener('show.bs.modal', function (event) {
        const button = event.relatedTarget;
        const id = button?.getAttribute('data-id') || '';
        const input = deleteModal.querySelector('#delete-id');
        if (input) input.value = id;
    });
}
</script>
</body>
</html>
// by ahmed  Abosultan
// by ahmed  Abosultan
// by ahmed  Abosultan
// by ahmed  Abosultan
