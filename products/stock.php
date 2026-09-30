<?php
require_once __DIR__ . '/../includes/auth.php';
require_login();
$active_menu='products';
$products=all_rows("SELECT product_id, product_name, quantity, reorder_level FROM products WHERE status='Active' ORDER BY product_name");
if($_SERVER['REQUEST_METHOD']==='POST'){
    require_csrf_or_redirect('products/stock.php');$productId=post_id('product_id');$quantity=posted('quantity');$errors=[];
    if($productId<1 || !scalar('SELECT COUNT(*) FROM products WHERE product_id=?',[$productId]))$errors[]='Please select a valid product.';
    if(!ctype_digit($quantity)|| (int)$quantity<1)$errors[]='Stock quantity must be a whole number of at least 1.';
    if($errors){$_SESSION['stock_errors']=$errors;$_SESSION['stock_form']=['product_id'=>$productId,'quantity'=>$quantity];redirect('products/stock.php');}
    $statement=db()->prepare('UPDATE products SET quantity=quantity+? WHERE product_id=?');$statement->execute([(int)$quantity,$productId]);set_flash('success','Stock added successfully.');redirect('products/index.php');
}
$form=$_SESSION['stock_form']??['product_id'=>'','quantity'=>''];unset($_SESSION['stock_form']);$errors=$_SESSION['stock_errors']??[];unset($_SESSION['stock_errors']);$page_title='Add Product Stock';require __DIR__.'/../includes/header.php';
?>
<div class="card form-card shadow-sm"><div class="card-body p-4"><p class="text-secondary">This operation adds to the current stock count and preserves all recorded product usage.</p><?php if($errors):?><div class="alert alert-danger"><ul class="mb-0"><?php foreach($errors as $error):?><li><?=e($error)?></li><?php endforeach;?></ul></div><?php endif;?><form method="post"><?=csrf_input()?><div class="row"><div class="col-md-8 mb-3"><label class="form-label">Product</label><select class="form-select" name="product_id" required><option value="">Choose product…</option><?php foreach($products as $product):?><option value="<?= (int)$product['product_id']?>" <?= (int)$form['product_id']===(int)$product['product_id']?'selected':''?>><?=e($product['product_name'])?> — <?= (int)$product['quantity']?> in stock</option><?php endforeach;?></select></div><div class="col-md-4 mb-3"><label class="form-label">Quantity to add</label><input class="form-control" type="number" name="quantity" min="1" step="1" value="<?=e($form['quantity'])?>" required></div></div><button class="btn btn-primary">Add stock</button> <a class="btn btn-outline-secondary" href="<?=e(app_url('products/index.php'))?>">Cancel</a></form></div></div>
<?php require __DIR__.'/../includes/footer.php';
