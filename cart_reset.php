<?php
session_start();
unset($_SESSION['cart']);
unset($_SESSION['product_cart']);
session_write_close();
echo "Cart cleared. <a href='cart.php'>Go to cart</a> | <a href='products.php'>Shop</a>";