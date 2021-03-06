<?php
/*
 Template Name: Corte Web
 */
?>
<?php 
//======================================================================
// Functions to order and know the total of products pending elaboration oriented to the agri-food industry
// Domicb 16/11/2021 https://github.com/domicb
// applied and tested in woocommerce 5.9.0
//======================================================================
header('Content-Type: text/html; charset=utf-8');
//insert CDN BOOSTRAP
echo '<link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.3.1/css/bootstrap.min.css" integrity="sha384-ggOyR0iXCbMQv3Xipma34MD+dH/1fQ784/j6cY/iJTQUOhcWr7x9JvoRxT2MZw1T" crossorigin="anonymous">';
$date = date('Y-m-d');
echo '
<div><center>
    <form action="/corte-web/" method="post">
        <div class="btn-group btn-group-justified" role="group" aria-label="...">
        <div class="btn-group" role="group">
            <button type="submit" name="status" value="wc-processing" class="btn btn-info">Productos vendidos</button>
        </div>
        <div class="btn-group" role="group">
            <button type="submit" name="status" value="wc-redsys-residentp" class="btn btn-success">Con Etiqueta MRW</button>
        </div>
        <div class="btn-group" role="group">
            <button type="submit" name="status" value="wc-on-hold" class="btn btn-warning">En espera</button>
        </div>
        </div>
    </form>
</center></div>';

//PARSE POST STATUS
$findfill = $_POST['status'];
if($findfill == 'wc-redsys-residentp') { $findfill = 'DOMICILIADO';}
if($findfill == 'wc-on-hold') { $findfill = 'EN ESPERA';}
if($findfill == 'wc-processing') { $findfill = 'PROCESANDO';}

global $wpdb;
//======================================================================
//SELECT PRODUCTS FOR ORDERS WP
//======================================================================
$resultados = $wpdb->get_results( "select
    p.ID as order_id,
    p.post_date,
    max( CASE WHEN pm.meta_key = '_billing_email' and p.ID = pm.post_id THEN pm.meta_value END ) as billing_email,
    max( CASE WHEN pm.meta_key = '_billing_first_name' and p.ID = pm.post_id THEN pm.meta_value END ) as _billing_first_name,
    max( CASE WHEN pm.meta_key = '_billing_last_name' and p.ID = pm.post_id THEN pm.meta_value END ) as _billing_last_name,
    max( CASE WHEN pm.meta_key = '_billing_address_1' and p.ID = pm.post_id THEN pm.meta_value END ) as _billing_address_1,
    max( CASE WHEN pm.meta_key = '_billing_address_2' and p.ID = pm.post_id THEN pm.meta_value END ) as _billing_address_2,
    max( CASE WHEN pm.meta_key = '_billing_city' and p.ID = pm.post_id THEN pm.meta_value END ) as _billing_city,
    max( CASE WHEN pm.meta_key = '_billing_state' and p.ID = pm.post_id THEN pm.meta_value END ) as _billing_state,
    max( CASE WHEN pm.meta_key = '_billing_postcode' and p.ID = pm.post_id THEN pm.meta_value END ) as _billing_postcode,
    max( CASE WHEN pm.meta_key = '_shipping_first_name' and p.ID = pm.post_id THEN pm.meta_value END ) as _shipping_first_name,
    max( CASE WHEN pm.meta_key = '_shipping_last_name' and p.ID = pm.post_id THEN pm.meta_value END ) as _shipping_last_name,
    max( CASE WHEN pm.meta_key = '_shipping_address_1' and p.ID = pm.post_id THEN pm.meta_value END ) as _shipping_address_1,
    max( CASE WHEN pm.meta_key = '_shipping_address_2' and p.ID = pm.post_id THEN pm.meta_value END ) as _shipping_address_2,
    max( CASE WHEN pm.meta_key = '_shipping_city' and p.ID = pm.post_id THEN pm.meta_value END ) as _shipping_city,
    max( CASE WHEN pm.meta_key = '_shipping_state' and p.ID = pm.post_id THEN pm.meta_value END ) as _shipping_state,
    max( CASE WHEN pm.meta_key = '_shipping_postcode' and p.ID = pm.post_id THEN pm.meta_value END ) as _shipping_postcode,
    max( CASE WHEN pm.meta_key = '_order_total' and p.ID = pm.post_id THEN pm.meta_value END ) as order_total,
    max( CASE WHEN pm.meta_key = '_order_tax' and p.ID = pm.post_id THEN pm.meta_value END ) as order_tax,
    max( CASE WHEN pm.meta_key = '_paid_date' and p.ID = pm.post_id THEN pm.meta_value END ) as paid_date,
    ( select group_concat( order_item_name separator ' | ' ) from wp_woocommerce_order_items where order_id = p.ID and order_item_type = 'line_item') as order_items
from
    wp_posts p 
    join wp_postmeta pm on p.ID = pm.post_id
    join wp_woocommerce_order_items oi on p.ID = oi.order_id
where
	post_status = '".$_POST['status']."'
group by
    p.ID LIMIT 80;");



if($resultados)
{
    $arrayIDproductPedidos[0][0] = -1;
    $arrayIDproduct[0] = -1;
    $contador = 0;
    $contadorCocidos = 0;
    $contadorCrudos = 0;
    $contadorTotal = 0;
    $sumaKilos = 0;
    $suma = 0;
    $indiceArrays = 0;
    echo '<table class="table table-bordered"><thead class="thead-dark"><tr><th scope="col">PRODUCTO</th><th scope="col"> CANTIDAD </th></tr></thead>';
    //var_dump($resultados);
    foreach ( $resultados as $resultado ) {//FOREACH ORDERS
        //echo 'PEDIDO EN ESTADO PROCESANDO N?? '.$contador++.'<br>';
        $order = wc_get_order( $resultado->order_id );
        $contador++;
        // Iterating through each WC_Order_Item_Product objects
        foreach ($order->get_items() as $item_key => $item ):     //FOREACH PRODUCTS 
            ## Using WC_Order_Item methods ##
            // Item ID is directly accessible from the $item_key in the foreach loop or
            $item_id = $item->get_id();
            ## Using WC_Order_Item_Product methods ##
            $product      = $item->get_product(); // Get the WC_Product object
            $product_id   = $item->get_product_id(); // the Product id
            $variation_id = $item->get_variation_id(); // the Variation id
            $item_type    = $item->get_type(); // Type of the order item ("line_item")
            $item_name    = $item->get_name(); // Name of the product
            $quantity     = $item->get_quantity();  
            $sumaKilos = $sumaKilos + $quantity;
            ## Access Order Items data properties (in an array of values) ##
            $item_data    = $item->get_data();
            $cocido = $item_data;
            $product_name = $item_data['name'];
            $product_id   = $item_data['product_id'];
            $variation_id = $item_data['variation_id'];
            $quantity     = $item_data['quantity'];
            $cadenaFiltro = ' ';
            $conElabor = 0;
            $keyIDinArray;
            $Variante = false;

            if($indiceArrays == 0 || in_array($product_id,$arrayIDproduct) == false &&  in_array($variation_id,$arrayIDproduct) == false){//the firts or no coincidence
                
                if($variation_id !=0 && $variation_id != ''){//if exist variation
                    $arrayIDproduct[$indiceArrays] = $variation_id;
                }else{
                    $arrayIDproduct[$indiceArrays] = $product_id;
                }        
                $arrayName[$indiceArrays]= $product_name;
                $arrayQuantity[$indiceArrays] = $quantity;
                $indiceArrays++;
                
            }else{
                if($variation_id !=0){//variaton id or product id
                    $Variante = true;
                }else{
                    $Variante = false;
                }   

                if($Variante == true){
                    $abuscar = $variation_id;
                }else{
                    $abuscar = $product_id;
                }

                foreach($arrayIDproduct as $key => $value){
                    if($abuscar == $value){//find idproduct or idvariation
                        $keyIDinArray = $key;
                    }
                }
                $QAnterior = $arrayQuantity[$keyIDinArray];
                $arrayQuantity[$keyIDinArray] = $QAnterior + $quantity;              
                    
            }     
            // Get data from The WC_product object using methods (examples)
            $product        = $item->get_product(); // Get the WC_Product object
        endforeach;       
    }
    $finalIDS = count($arrayIDproduct);//length

    $wpdb->query( "DELETE FROM wp_corte WHERE 1");
    for ($pri = 0; $pri < $finalIDS; $pri++){//insert for later order by
        $wpdb->insert('wp_corte', array(
            'name' => $arrayName[$pri],
            'cantidad' => $arrayQuantity[$pri]
        ));
    }

    $resOrders = $wpdb->get_results( "select * from wp_corte order by name ");
    foreach ($resOrders as $res0rder) {
        echo '<tr><th>'. $res0rder->name .'</th></td><td> '.$res0rder->cantidad.'</td></tr>';
    }
    echo '<div class="alert alert-primary" role="alert">Quedan por elaborar un total de : '.$sumaKilos.' productos para un total de '.$contador.' pedidos </div>';

}else{echo 'No hay pedidos con este estado';}

?>

