<?php
$servidor = "sql302.infinityfree.com";
$usuario = "if0_42190175";
$clave = "l8Z0FOMGcAro";
$bd = "if0_42190175_dark_kitchen";

$enlace = mysqli_connect($servidor, $usuario, $clave, $bd);

// Verifica conexión y muestra error real
if (mysqli_connect_errno()) {
    die("Error de conexión: " . mysqli_connect_error());
}
// Función para actualizar la disponibilidad de un producto basado en insumos
function actualizarDisponibilidadProducto($enlace, $id_producto) {
    $sql = "
        SELECT 
            MIN(ROUND(i.cantidad_disponible / pi.cantidad_usada)) as max_unidades
        FROM productoinsumo pi
        INNER JOIN insumo i ON pi.id_insumo = i.id_insumo
        WHERE pi.id_producto = $id_producto
    ";
    $res = mysqli_query($enlace, $sql);
    $row = mysqli_fetch_assoc($res);
    $max_unidades = $row['max_unidades'] ?? 0;
    
    $disponible = ($max_unidades > 0) ? 1 : 0;
    mysqli_query($enlace, "UPDATE producto SET disponible = $disponible WHERE id_producto = $id_producto");
    
    return $disponible;
}
mysqli_set_charset($enlace, "utf8");
?>