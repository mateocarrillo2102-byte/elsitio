<?php
session_start();
include __DIR__ . "/conexion.php";

// ========== MANEJAR PETICIONES AJAX ==========
$is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
           strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest';

if ($is_ajax) {
    $accion = $_POST['accion'] ?? $_GET['accion'] ?? '';
    
    // Agregar al carrito
    if ($accion == 'agregar') {
        $id_producto = $_POST['id_producto'];
        
        // Verificar si hay suficientes insumos para este producto
        $sql_check = "
            SELECT 
                MIN(ROUND(i.cantidad_disponible / pi.cantidad_usada)) as max_unidades
            FROM productoinsumo pi
            INNER JOIN insumo i ON pi.id_insumo = i.id_insumo
            WHERE pi.id_producto = $id_producto
        ";
        $res_check = mysqli_query($enlace, $sql_check);
        $row_check = mysqli_fetch_assoc($res_check);
        $max_unidades = $row_check['max_unidades'] ?? 999;
        
        $carrito_actual = $_SESSION['carrito'][$id_producto] ?? 0;
        
        if ($max_unidades > $carrito_actual) {
            if (!isset($_SESSION['carrito'][$id_producto])) {
                $_SESSION['carrito'][$id_producto] = 1;
            } else {
                $_SESSION['carrito'][$id_producto]++;
            }
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['success' => false, 'message' => 'No hay suficientes insumos para más unidades de este producto']);
        }
        exit;
    }
    
    // Obtener carrito
    if ($accion == 'obtener') {
        $items = [];
        $total = 0;
        $total_items = 0;
        
        if (!empty($_SESSION['carrito'])) {
            $ids = implode(',', array_keys($_SESSION['carrito']));
            $sql = "SELECT * FROM producto WHERE id_producto IN ($ids)";
            $resultado = mysqli_query($enlace, $sql);
            
            while ($producto = mysqli_fetch_assoc($resultado)) {
                $cantidad = $_SESSION['carrito'][$producto['id_producto']];
                $subtotal = $producto['precio'] * $cantidad;
                $total += $subtotal;
                $total_items += $cantidad;
                
                $items[] = [
                    'id' => $producto['id_producto'],
                    'nombre' => $producto['nombre'],
                    'precio' => (int)$producto['precio'],
                    'cantidad' => $cantidad,
                    'subtotal' => $subtotal
                ];
            }
        }
        
        echo json_encode([
            'success' => true,
            'items' => $items,
            'total' => $total,
            'total_items' => $total_items
        ]);
        exit;
    }
    
    // Actualizar cantidad
    if ($accion == 'actualizar') {
        $id_producto = $_POST['id_producto'];
        $tipo = $_POST['tipo'];
        
        if (isset($_SESSION['carrito'][$id_producto])) {
            if ($tipo == 'incrementar') {
                $_SESSION['carrito'][$id_producto]++;
            } elseif ($tipo == 'decrementar') {
                $_SESSION['carrito'][$id_producto]--;
                if ($_SESSION['carrito'][$id_producto] <= 0) {
                    unset($_SESSION['carrito'][$id_producto]);
                }
            } elseif ($tipo == 'eliminar') {
                unset($_SESSION['carrito'][$id_producto]);
            }
        }
        echo json_encode(['success' => true]);
        exit;
    }
    
    // Realizar pedido
    if ($accion == 'finalizar_pedido') {
        if (empty($_SESSION['carrito'])) {
            echo json_encode(['success' => false, 'message' => 'Carrito vacío']);
            exit;
        }
        
        $observacion = mysqli_real_escape_string($enlace, $_POST['observaciones'] ?? '');
        $metodo_pago = mysqli_real_escape_string($enlace, $_POST['metodo_pago'] ?? 'efectivo');
        $id_cliente = 1; // Temporal, luego con sesión de usuario
        
        mysqli_begin_transaction($enlace);
        
        try {
            // Calcular total
            $total = 0;
            foreach ($_SESSION['carrito'] as $id_producto => $cantidad) {
                $sql_precio = "SELECT precio FROM producto WHERE id_producto = $id_producto";
                $res_precio = mysqli_query($enlace, $sql_precio);
                $producto_precio = mysqli_fetch_assoc($res_precio);
                $total += $producto_precio['precio'] * $cantidad;
            }
            
            // Verificar insumos
            foreach ($_SESSION['carrito'] as $id_producto => $cantidad) {
                $sql_check = "
                    SELECT MIN(ROUND(i.cantidad_disponible / pi.cantidad_usada)) as max_unidades
                    FROM productoinsumo pi
                    INNER JOIN insumo i ON pi.id_insumo = i.id_insumo
                    WHERE pi.id_producto = $id_producto
                ";
                $res_check = mysqli_query($enlace, $sql_check);
                $row_check = mysqli_fetch_assoc($res_check);
                $max_unidades = $row_check['max_unidades'] ?? 0;
                
                if ($max_unidades < $cantidad) {
                    throw new Exception("No hay suficientes insumos para " . $cantidad . " unidades de este producto");
                }
            }
            
            // Insertar pedido
            $sql_pedido = "INSERT INTO pedido (fecha_pedido, total, observaciones, id_cliente, metodo_pago, estado) 
                           VALUES (NOW(), $total, '$observacion', $id_cliente, '$metodo_pago', 'recibido')";
            
            if (!mysqli_query($enlace, $sql_pedido)) {
                throw new Exception("Error al guardar pedido: " . mysqli_error($enlace));
            }
            
            $id_pedido = mysqli_insert_id($enlace);
            
            // Insertar detalles y descontar
            foreach ($_SESSION['carrito'] as $id_producto => $cantidad) {
                $sql_producto = "SELECT * FROM producto WHERE id_producto = $id_producto";
                $resultado = mysqli_query($enlace, $sql_producto);
                $producto = mysqli_fetch_assoc($resultado);
                $subtotal = $producto['precio'] * $cantidad;
                
                $sql_detalle = "INSERT INTO detallepedido (cantidad, subtotal, id_pedido, id_producto) 
                                VALUES ($cantidad, $subtotal, $id_pedido, $id_producto)";
                mysqli_query($enlace, $sql_detalle);
                
                // Descontar stock
                mysqli_query($enlace, "UPDATE producto SET stock = stock - $cantidad 
                                       WHERE id_producto = $id_producto");
                
                // Descontar insumos
                $sql_insumos = "SELECT id_insumo, cantidad_usada FROM productoinsumo WHERE id_producto = $id_producto";
                $res_insumos = mysqli_query($enlace, $sql_insumos);
                
                while ($insumo = mysqli_fetch_assoc($res_insumos)) {
                    $cantidad_necesaria = $insumo['cantidad_usada'] * $cantidad;
                    mysqli_query($enlace, "UPDATE insumo SET cantidad_disponible = cantidad_disponible - $cantidad_necesaria 
                                           WHERE id_insumo = " . $insumo['id_insumo']);
                    mysqli_query($enlace, "INSERT INTO movimientoinventario (tipo, cantidad, descripcion, id_insumo) 
                                           VALUES ('salida', $cantidad_necesaria, 'Pedido #$id_pedido', " . $insumo['id_insumo'] . ")");
                }
            }
            
            mysqli_commit($enlace);
            unset($_SESSION['carrito']);
            
            echo json_encode(['success' => true]);
            exit;
            
        } catch (Exception $e) {
            mysqli_rollback($enlace);
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }
    
    // Vaciar carrito
    if ($accion == 'vaciar') {
        unset($_SESSION['carrito']);
        echo json_encode(['success' => true]);
        exit;
    }
    
    echo json_encode(['success' => false, 'message' => 'Acción no válida']);
    exit;
}

// ========== VISTA HTML DEL CARRITO ==========
$total = 0;
$productos_carrito = [];

if (!empty($_SESSION['carrito'])) {
    $ids = implode(',', array_keys($_SESSION['carrito']));
    $sql = "SELECT * FROM producto WHERE id_producto IN ($ids)";
    $resultado = mysqli_query($enlace, $sql);
    
    while ($producto = mysqli_fetch_assoc($resultado)) {
        $cantidad = $_SESSION['carrito'][$producto['id_producto']];
        $subtotal = $producto['precio'] * $cantidad;
        $total += $subtotal;
        $productos_carrito[] = [
            'id' => $producto['id_producto'],
            'nombre' => $producto['nombre'],
            'precio' => $producto['precio'],
            'cantidad' => $cantidad,
            'subtotal' => $subtotal
        ];
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Mi Carrito - El Sitio</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'Inter', sans-serif; background: #0a0a0f; color: #fff; }
        .header { background: linear-gradient(135deg, #0a0a0f, #11111a); padding: 40px 20px; text-align: center; border-bottom: 1px solid rgba(0,245,255,0.2); }
        .header h1 { font-size: 2rem; background: linear-gradient(135deg, #00f5ff, #ff2d9e); -webkit-background-clip: text; background-clip: text; color: transparent; }
        .header a { display: inline-block; margin-top: 15px; color: #00f5ff; text-decoration: none; }
        .container { max-width: 1200px; margin: 0 auto; padding: 40px 20px; }
        .cart-grid { display: grid; grid-template-columns: 1fr 350px; gap: 30px; }
        .cart-table { background: #11111a; border-radius: 20px; overflow: hidden; border: 1px solid rgba(0,245,255,0.1); }
        .cart-table-header { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 0.5fr; background: #1c1c28; padding: 15px 20px; font-weight: 600; color: #00f5ff; border-bottom: 1px solid rgba(0,245,255,0.2); }
        .cart-item { display: grid; grid-template-columns: 2fr 1fr 1fr 1fr 0.5fr; padding: 15px 20px; border-bottom: 1px solid #1c1c28; align-items: center; }
        .product-name { font-weight: 600; }
        .product-price { color: #ffe600; }
        .quantity-controls { display: flex; gap: 8px; align-items: center; }
        .qty-btn { width: 28px; height: 28px; background: transparent; border: 1px solid #00f5ff; color: #00f5ff; border-radius: 6px; cursor: pointer; transition: 0.3s; }
        .qty-btn:hover { background: #00f5ff; color: #0a0a0f; }
        .qty { font-weight: 600; }
        .remove-item { background: none; border: none; color: #ff2d9e; font-size: 1.2rem; cursor: pointer; }
        .product-subtotal { color: #00f5ff; font-weight: 700; }
        .order-summary { background: #11111a; border-radius: 20px; padding: 25px; border: 1px solid rgba(0,245,255,0.1); height: fit-content; }
        .order-summary h3 { color: #00f5ff; margin-bottom: 20px; }
        textarea { width: 100%; background: #1c1c28; border: 1px solid rgba(0,245,255,0.2); border-radius: 10px; padding: 12px; color: #fff; font-family: inherit; resize: vertical; margin: 15px 0; }
        
        /* Métodos de pago */
        .payment-methods { margin: 15px 0; padding: 10px 0; border-top: 1px solid #1c1c28; border-bottom: 1px solid #1c1c28; }
        .payment-title { font-size: 0.85rem; color: #ffe600; margin-bottom: 12px; display: block; letter-spacing: 1px; }
        .payment-option { display: flex; align-items: center; gap: 10px; padding: 10px 12px; margin: 8px 0; background: #1c1c28; border: 1px solid #333; border-radius: 10px; cursor: pointer; transition: 0.3s; }
        .payment-option:hover { border-color: #00f5ff; background: #252535; }
        .payment-option input { accent-color: #00f5ff; width: 18px; height: 18px; cursor: pointer; }
        .payment-option .payment-icon { font-size: 1.3rem; }
        .payment-option .payment-name { flex: 1; font-size: 0.9rem; font-weight: 500; color: #fff; }
        .payment-option .payment-desc { font-size: 0.7rem; color: #888; }
        
        .total-row { display: flex; justify-content: space-between; padding: 15px 0; border-top: 1px solid #1c1c28; font-size: 1.2rem; font-weight: 700; }
        .total-row span:first-child { color: #ffe600; }
        .total-row span:last-child { color: #00f5ff; font-size: 1.4rem; }
        .btn-checkout { width: 100%; padding: 14px; background: linear-gradient(90deg, #00f5ff, #ff2d9e); border: none; border-radius: 10px; color: #0a0a0f; font-weight: 700; cursor: pointer; margin-top: 10px; }
        .btn-vaciar { width: 100%; padding: 12px; background: transparent; border: 1px solid #ff2d9e; border-radius: 10px; color: #ff2d9e; font-weight: 600; cursor: pointer; margin-top: 10px; }
        .empty-cart { text-align: center; padding: 60px 20px; background: #11111a; border-radius: 20px; }
        .btn-seguir { display: inline-block; margin-top: 20px; padding: 12px 30px; background: #00f5ff; color: #0a0a0f; text-decoration: none; border-radius: 10px; font-weight: 600; }
        .notification { position: fixed; bottom: 20px; right: 20px; background: #00f5ff; color: #0a0a0f; padding: 12px 24px; border-radius: 10px; font-weight: 600; z-index: 1000; animation: slideIn 0.3s ease; }
        .notification.error { background: #ff2d9e; color: white; }
        @keyframes slideIn { from { transform: translateX(100%); opacity: 0; } to { transform: translateX(0); opacity: 1; } }
        @media (max-width: 768px) { .cart-grid { grid-template-columns: 1fr; } .cart-table-header, .cart-item { grid-template-columns: 2fr 0.8fr 1fr 0.8fr 0.3fr; gap: 10px; font-size: 0.8rem; } }
    </style>
</head>
<body>

<div class="header">
    <h1>🛒 Mi Carrito</h1>
    <a href="menu.php">← Seguir comprando</a>
</div>

<div class="container">
    <?php if (empty($productos_carrito)): ?>
        <div class="empty-cart">
            <div class="empty-icon">🛍️</div>
            <h3>Tu carrito está vacío</h3>
            <p>Agrega productos desde el menú para comenzar</p>
            <a href="menu.php" class="btn-seguir">Ver Menú</a>
        </div>
    <?php else: ?>
        <div class="cart-grid">
            <div class="cart-table">
                <div class="cart-table-header">
                    <span>Producto</span>
                    <span>Precio</span>
                    <span>Cantidad</span>
                    <span>Subtotal</span>
                    <span></span>
                </div>
                <div id="cart-items-container">
                    <?php foreach ($productos_carrito as $item): ?>
                        <div class="cart-item" data-id="<?php echo $item['id']; ?>">
                            <span class="product-name"><?php echo htmlspecialchars($item['nombre']); ?></span>
                            <span class="product-price">$<?php echo number_format($item['precio']); ?></span>
                            <div class="quantity-controls">
                                <button class="qty-btn minus" data-id="<?php echo $item['id']; ?>">-</button>
                                <span class="qty"><?php echo $item['cantidad']; ?></span>
                                <button class="qty-btn plus" data-id="<?php echo $item['id']; ?>">+</button>
                            </div>
                            <span class="product-subtotal">$<?php echo number_format($item['subtotal']); ?></span>
                            <button class="remove-item" data-id="<?php echo $item['id']; ?>">🗑️</button>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <div class="order-summary">
                <h3>Resumen del pedido</h3>
                <textarea id="observaciones" rows="3" placeholder="📝 ¿Alguna observación? (Ej: Sin cebolla, entregar en portería...)"></textarea>
                
                <!-- MÉTODOS DE PAGO -->
                <div class="payment-methods">
                    <span class="payment-title">💰 MÉTODO DE PAGO</span>
                    
                    <label class="payment-option">
                        <input type="radio" name="metodo_pago" value="efectivo" checked>
                        <span class="payment-icon">💵</span>
                        <span class="payment-name">Efectivo al recibir</span>
                        <span class="payment-desc">Paga cuando recibas tu pedido</span>
                    </label>
                    
                    <label class="payment-option">
                        <input type="radio" name="metodo_pago" value="tarjeta">
                        <span class="payment-icon">💳</span>
                        <span class="payment-name">Tarjeta débito/crédito</span>
                        <span class="payment-desc">Visa, Mastercard, American Express</span>
                    </label>
                    
                    <label class="payment-option">
                        <input type="radio" name="metodo_pago" value="transferencia">
                        <span class="payment-icon">🏦</span>
                        <span class="payment-name">Transferencia bancaria</span>
                        <span class="payment-desc">Nequi, Daviplata, Bancolombia</span>
                    </label>
                </div>
                
                <div class="total-row">
                    <span>Total:</span>
                    <span id="total-amount">$<?php echo number_format($total); ?></span>
                </div>
                <button class="btn-checkout" id="checkoutBtn">✅ Realizar Pedido</button>
                <button class="btn-vaciar" id="vaciarBtn">🗑️ Vaciar Carrito</button>
            </div>
        </div>
    <?php endif; ?>
</div>

<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script>
$(document).ready(function() {
    function formatearNumero(num) { return num.toString().replace(/\B(?=(\d{3})+(?!\d))/g, "."); }
    function mostrarNotificacion(mensaje, error = false) {
        var notif = $('<div class="notification">' + mensaje + '</div>');
        if (error) notif.addClass('error');
        $('body').append(notif);
        setTimeout(() => notif.fadeOut(300, () => notif.remove()), 2000);
    }
    
    function actualizarCarrito() {
        $.ajax({
            url: 'carrito.php',
            method: 'GET',
            data: { accion: 'obtener' },
            dataType: 'json',
            success: function(response) {
                if (response.success && response.items && response.items.length > 0) {
                    var itemsHtml = '';
                    response.items.forEach(function(item) {
                        itemsHtml += `
                            <div class="cart-item" data-id="${item.id}">
                                <span class="product-name">${item.nombre}</span>
                                <span class="product-price">$${formatearNumero(item.precio)}</span>
                                <div class="quantity-controls">
                                    <button class="qty-btn minus" data-id="${item.id}">-</button>
                                    <span class="qty">${item.cantidad}</span>
                                    <button class="qty-btn plus" data-id="${item.id}">+</button>
                                </div>
                                <span class="product-subtotal">$${formatearNumero(item.subtotal)}</span>
                                <button class="remove-item" data-id="${item.id}">🗑️</button>
                            </div>
                        `;
                    });
                    $('#cart-items-container').html(itemsHtml);
                    $('#total-amount').html('$' + formatearNumero(response.total));
                } else {
                    location.reload();
                }
            }
        });
    }
    
    $(document).on('click', '.plus', function() {
        var id = $(this).data('id');
        $.ajax({ url: 'carrito.php', method: 'POST', data: { accion: 'actualizar', id_producto: id, tipo: 'incrementar' }, dataType: 'json', success: () => actualizarCarrito() });
    });
    $(document).on('click', '.minus', function() {
        var id = $(this).data('id');
        $.ajax({ url: 'carrito.php', method: 'POST', data: { accion: 'actualizar', id_producto: id, tipo: 'decrementar' }, dataType: 'json', success: () => actualizarCarrito() });
    });
    $(document).on('click', '.remove-item', function() {
        var id = $(this).data('id');
        $.ajax({ url: 'carrito.php', method: 'POST', data: { accion: 'actualizar', id_producto: id, tipo: 'eliminar' }, dataType: 'json', success: () => actualizarCarrito() });
    });
    
    $('#vaciarBtn').click(function() {
        if (confirm('¿Estás seguro de vaciar el carrito?')) {
            $.ajax({ url: 'carrito.php', method: 'POST', data: { accion: 'vaciar' }, dataType: 'json', success: () => location.reload() });
        }
    });
    
    $('#checkoutBtn').click(function() {
        var observaciones = $('#observaciones').val();
        var metodo_pago = $('input[name="metodo_pago"]:checked').val();
        
        if (confirm('¿Confirmar pedido con pago en ' + (metodo_pago === 'efectivo' ? 'efectivo' : metodo_pago === 'tarjeta' ? 'tarjeta' : 'transferencia') + '?')) {
            $.ajax({
                url: 'carrito.php',
                method: 'POST',
                data: { 
                    accion: 'finalizar_pedido', 
                    observaciones: observaciones,
                    metodo_pago: metodo_pago
                },
                dataType: 'json',
                success: function(response) {
                    if (response.success) {
                        mostrarNotificacion('✅ ¡Pedido realizado con éxito!');
                        setTimeout(() => window.location.href = 'mis_pedidos.php', 2000);
                    } else {
                        mostrarNotificacion('❌ Error: ' + response.message, true);
                    }
                }
            });
        }
    });
});
</script>
</body>
</html>