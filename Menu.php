<?php
session_start();
include "conexion.php";

// Inicializar carrito si no existe
if (!isset($_SESSION['carrito'])) {
    $_SESSION['carrito'] = [];
}

// Obtener solo productos VISIBLES y que pueden prepararse (insumos suficientes)
$sql_productos = "
    SELECT p.*, 
           (SELECT MIN(ROUND(i.cantidad_disponible / pi.cantidad_usada)) 
            FROM productoinsumo pi 
            INNER JOIN insumo i ON pi.id_insumo = i.id_insumo 
            WHERE pi.id_producto = p.id_producto) as max_unidades
    FROM producto p
    WHERE p.visible = 1
    HAVING max_unidades > 0 OR max_unidades IS NULL
    ORDER BY p.nombre
";
$resultado_productos = mysqli_query($enlace, $sql_productos);
$productos = [];
while ($row = mysqli_fetch_assoc($resultado_productos)) {
    // Calcular máximo de unidades que se pueden preparar
    $max_unidades = $row['max_unidades'] ?? 999;
    $row['max_unidades'] = $max_unidades;
    $productos[] = $row;
}

// Calcular total del carrito para mostrarlo en el sidebar
$total_carrito = 0;
$items_carrito = [];
$total_items = 0;
foreach ($_SESSION['carrito'] as $id_producto => $cantidad) {
    foreach ($productos as $producto) {
        if ($producto['id_producto'] == $id_producto) {
            $subtotal = $producto['precio'] * $cantidad;
            $total_carrito += $subtotal;
            $total_items += $cantidad;
            $items_carrito[] = [
                'id' => $producto['id_producto'],
                'nombre' => $producto['nombre'],
                'precio' => $producto['precio'],
                'cantidad' => $cantidad,
                'subtotal' => $subtotal
            ];
            break;
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>El Sitio - Menú</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="Menu.css">
</head>
<body>

<div class="overlay" id="overlay"></div>

<!-- Carrito lateral -->
<aside class="cart-sidebar" id="cartSidebar">
    <div class="cart-header">
        <h2>🛒 Mi Carrito</h2>
        <button class="close-cart" id="closeCartBtn">✕</button>
    </div>
    <div class="cart-items" id="cartItems">
        <?php if (empty($items_carrito)): ?>
            <div class="empty-cart">🛍️ El carrito está vacío</div>
        <?php else: ?>
            <?php foreach ($items_carrito as $item): ?>
                <div class="cart-item" data-id="<?= $item['id'] ?>">
                    <div class="cart-item-info">
                        <h4><?= htmlspecialchars($item['nombre']) ?></h4>
                        <div class="cart-item-price">$<?= number_format($item['precio']) ?></div>
                        <div class="cart-item-actions">
                            <button class="qty-btn minus" data-id="<?= $item['id'] ?>">-</button>
                            <span class="qty"><?= $item['cantidad'] ?></span>
                            <button class="qty-btn plus" data-id="<?= $item['id'] ?>">+</button>
                            <button class="remove-btn" data-id="<?= $item['id'] ?>">🗑️</button>
                        </div>
                    </div>
                    <div class="cart-item-subtotal">$<?= number_format($item['subtotal']) ?></div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>
    <div class="cart-footer">
        <div class="cart-total">
            <span>Total:</span>
            <strong id="cartTotal">$<?= number_format($total_carrito) ?></strong>
        </div>
        <button class="btn-checkout" id="checkoutBtn">✅ Realizar Pedido</button>
    </div>
</aside>

<!-- Botón flotante carrito -->
<button class="cart-mobile-btn" id="showCartBtn">
    🛒
    <span class="cart-count-badge" id="cartCount"><?= $total_items ?></span>
</button>

<!-- Header principal -->
<header class="main-header">
    <div class="logo">
        <h1>🍔 COMIDAS RÁPIDAS EL SITIO</h1>
    </div>
    <p class="tagline">Los mejores sabores de la ciudad | RÁPIDO, SEGURO Y CERCA DE TI</p>
    <div style="display: flex; justify-content: center; gap: 15px; margin-top: 15px;">
        <a href="mis_pedidos.php" class="btn-mis-pedidos">📋 Ver Mis Pedidos</a>
        <a href="login.php" class="btn-logout">🚪 Cerrar Sesión</a>
    </div>
</header>

<!-- Buscador y filtros -->
<div class="search-section">
    <div class="search-box">
        <span>🔍</span>
        <input type="text" id="searchInput" placeholder="Buscar producto...">
    </div>
    <div class="filters">
        <button class="filter-btn active" data-filter="all">Todos</button>
        <button class="filter-btn" data-filter="hamburguesas">🍔 Hamburguesas</button>
        <button class="filter-btn" data-filter="perros">🌭 Perros</button>
        <button class="filter-btn" data-filter="otros">🍟 Otros</button>
    </div>
</div>

<!-- Menú de productos -->
<main class="menu" id="productsGrid">
    <?php foreach ($productos as $producto): ?>
        <div class="card" data-category="<?php 
            if (strpos($producto['nombre'], 'Hamburguesa') !== false) echo 'hamburguesas';
            elseif (strpos($producto['nombre'], 'Perro') !== false) echo 'perros';
            else echo 'otros';
        ?>">
            <div class="card-img">
                <?php 
                    if (strpos($producto['nombre'], 'Hamburguesa') !== false) echo '🍔';
                    elseif (strpos($producto['nombre'], 'Perro') !== false) echo '🌭';
                    elseif (strpos($producto['nombre'], 'Salchipapa') !== false) echo '🍟';
                    elseif (strpos($producto['nombre'], 'Costilla') !== false) echo '🍖';
                    else echo '🥙';
                ?>
            </div>
            <div class="card-body">
                <h3><?= htmlspecialchars($producto['nombre']) ?></h3>
                <span class="precio">$<?= number_format($producto['precio']) ?></span>
                <?php if ($producto['max_unidades'] > 0): ?>
                    <button class="btn add-to-cart" data-id="<?= $producto['id_producto'] ?>">+ Agregar al carrito</button>
                <?php else: ?>
                    <button class="btn disabled" disabled style="opacity:0.5; cursor:not-allowed;">❌ Sin stock</button>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>
</main>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    
    // Agregar al carrito
    $('.add-to-cart').click(function() {
        var id = $(this).data('id');
        $.ajax({
            url: 'carrito.php',
            method: 'POST',
            data: { accion: 'agregar', id_producto: id },
            dataType: 'json',
            success: function(res) {
                if (res.success) {
                    actualizarCarrito();
                    mostrarNotificacion('✅ Producto agregado');
                } else {
                    mostrarNotificacion(res.message || '❌ Error al agregar', true);
                }
            }
        });
    });

    function actualizarCarrito() {
        $.ajax({
            url: 'carrito.php',
            method: 'GET',
            data: { accion: 'obtener' },
            dataType: 'json',
            success: function(res) {
                $('#cartCount').text(res.total_items);
                if (res.items && res.items.length) {
                    let html = '';
                    res.items.forEach(item => {
                        html += `
                            <div class="cart-item" data-id="${item.id}">
                                <div class="cart-item-info">
                                    <h4>${item.nombre}</h4>
                                    <div class="cart-item-price">$${formatearNumero(item.precio)}</div>
                                    <div class="cart-item-actions">
                                        <button class="qty-btn minus" data-id="${item.id}">-</button>
                                        <span class="qty">${item.cantidad}</span>
                                        <button class="qty-btn plus" data-id="${item.id}">+</button>
                                        <button class="remove-btn" data-id="${item.id}">🗑️</button>
                                    </div>
                                </div>
                                <div class="cart-item-subtotal">$${formatearNumero(item.subtotal)}</div>
                            </div>
                        `;
                    });
                    $('#cartItems').html(html);
                    $('#cartTotal').html('$' + formatearNumero(res.total));
                } else {
                    $('#cartItems').html('<div class="empty-cart">🛍️ El carrito está vacío</div>');
                    $('#cartTotal').html('$0');
                }
                asignarEventosCarrito();
            }
        });
    }

    function asignarEventosCarrito() {
        $('.plus').off('click').click(function() {
            let id = $(this).data('id');
            $.ajax({ url: 'carrito.php', method: 'POST', data: { accion: 'actualizar', id_producto: id, tipo: 'incrementar' }, success: () => actualizarCarrito() });
        });
        $('.minus').off('click').click(function() {
            let id = $(this).data('id');
            $.ajax({ url: 'carrito.php', method: 'POST', data: { accion: 'actualizar', id_producto: id, tipo: 'decrementar' }, success: () => actualizarCarrito() });
        });
        $('.remove-btn').off('click').click(function() {
            let id = $(this).data('id');
            $.ajax({ url: 'carrito.php', method: 'POST', data: { accion: 'actualizar', id_producto: id, tipo: 'eliminar' }, success: () => actualizarCarrito() });
        });
    }

    $('#checkoutBtn').click(function() {
        window.location.href = 'carrito.php';
    });

    $('#showCartBtn').click(() => $('#cartSidebar, #overlay').addClass('active'));
    $('#closeCartBtn, #overlay').click(() => $('#cartSidebar, #overlay').removeClass('active'));

    // Buscador
    $('#searchInput').on('keyup', function() {
        let term = $(this).val().toLowerCase();
        $('.card').each(function() {
            let name = $(this).find('h3').text().toLowerCase();
            $(this).toggle(name.indexOf(term) > -1);
        });
    });

    // Filtros
    $('.filter-btn').click(function() {
        $('.filter-btn').removeClass('active');
        $(this).addClass('active');
        let filter = $(this).data('filter');
        $('.card').each(function() {
            let cat = $(this).data('category');
            $(this).toggle(filter === 'all' || cat === filter);
        });
    });

    function mostrarNotificacion(msg, error = false) {
        let n = $('<div class="notification' + (error ? ' error' : '') + '">' + msg + '</div>');
        $('body').append(n);
        setTimeout(() => n.fadeOut(300, () => n.remove()), 2000);
    }

    function formatearNumero(n) {
        return n.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }

    actualizarCarrito();
});
</script>
</body>
</html>