<?php
session_start();

// --- 1. LÓGICA DE NEGOCIO (BACKEND PHP) ---

// Base de datos de comida
$comidas_db = [
    'empanada' => ['nombre' => 'Empanada', 'precio' => 5, 'hambre' => 20, 'energia' => 5, 'felicidad' => 5, 'color' => '#d8961c'],
    'changua' => ['nombre' => 'Changua', 'precio' => 10, 'hambre' => 30, 'energia' => 15, 'felicidad' => 0, 'color' => '#ffffff'],
    'sancocho' => ['nombre' => 'Sancocho', 'precio' => 25, 'hambre' => 60, 'energia' => 20, 'felicidad' => 15, 'color' => '#e8c92a'],
    'ajiaco' => ['nombre' => 'Ajiaco', 'precio' => 30, 'hambre' => 65, 'energia' => 25, 'felicidad' => 25, 'color' => '#d6ba38'],
    'arepa_rellena' => ['nombre' => 'Arepa Rellena', 'precio' => 18, 'hambre' => 45, 'energia' => 10, 'felicidad' => 15, 'color' => '#e6b853'],
    'chorizo' => ['nombre' => 'Chorizo con Arepa', 'precio' => 15, 'hambre' => 35, 'energia' => 10, 'felicidad' => 20, 'color' => '#a32b1d'],
    'bonbonbum' => ['nombre' => 'Bon Bon Bum', 'precio' => 3, 'hambre' => 5, 'energia' => 15, 'felicidad' => 20, 'color' => '#e61919'],
    'nucita' => ['nombre' => 'Nucita', 'precio' => 5, 'hambre' => 10, 'energia' => 10, 'felicidad' => 30, 'color' => '#6b4021']
];

if (!isset($_SESSION['stats'])) {
    $_SESSION['stats'] = [
        'hambre' => 50,
        'energia' => 100,
        'felicidad' => 50,
        'suciedad' => 0,
        'monedas' => 20, // Empieza con 20 monedas para probar la tienda
        'inventario' => [
            'empanada' => 2, 'changua' => 1, 'sancocho' => 0, 'ajiaco' => 0, 
            'arepa_rellena' => 0, 'chorizo' => 0, 'bonbonbum' => 0, 'nucita' => 0
        ]
    ];
}

if (isset($_GET['action'])) {
    header('Content-Type: application/json');
    $action = $_GET['action'];
    $stats = &$_SESSION['stats'];

    // Acción de comer
    if (strpos($action, 'comer_') === 0) {
        $item = str_replace('comer_', '', $action);
        if (isset($comidas_db[$item]) && $stats['inventario'][$item] > 0) {
            $stats['inventario'][$item]--;
            $comida = $comidas_db[$item];
            
            $stats['hambre'] = max(0, $stats['hambre'] - $comida['hambre']);
            $stats['energia'] = min(100, $stats['energia'] + $comida['energia']);
            $stats['felicidad'] = min(100, $stats['felicidad'] + $comida['felicidad']);
            $stats['suciedad'] = min(100, $stats['suciedad'] + 5); // Comer ensucia un poco
            
            echo json_encode(['status' => 'ok', 'stats' => $stats]);
            exit;
        }
        echo json_encode(['status' => 'error', 'msg' => 'No tienes este item']);
        exit;
    }

    // Acción de comprar
    if (strpos($action, 'comprar_') === 0) {
        $item = str_replace('comprar_', '', $action);
        if (isset($comidas_db[$item])) {
            $precio = $comidas_db[$item]['precio'];
            if ($stats['monedas'] >= $precio) {
                $stats['monedas'] -= $precio;
                $stats['inventario'][$item]++;
                echo json_encode(['status' => 'ok', 'stats' => $stats]);
                exit;
            } else {
                echo json_encode(['status' => 'error', 'msg' => 'Monedas insuficientes']);
                exit;
            }
        }
    }

    switch ($action) {
        case 'sleep_tick':
            if ($stats['energia'] < 100) {
                $stats['energia'] = min(100, $stats['energia'] + 5);
                $stats['hambre'] = min(100, $stats['hambre'] + 1); 
            }
            break;
        case 'ducha_completada':
            if ($stats['suciedad'] > 0) { $stats['monedas'] += 15; }
            $stats['suciedad'] = 0;
            $stats['felicidad'] = min(100, $stats['felicidad'] + 15);
            break;
        case 'jugar_tm':
            if ($stats['energia'] >= 20) {
                $stats['felicidad'] = min(100, $stats['felicidad'] + 35);
                $stats['energia'] = max(0, $stats['energia'] - 25);
                $stats['suciedad'] = min(100, $stats['suciedad'] + 15);
                $stats['hambre'] = min(100, $stats['hambre'] + 20);
                $stats['monedas'] += 5; 
            }
            break;
        case 'recompensa_pelota':
            if ($stats['energia'] >= 5) {
                $stats['monedas'] += 2;
                $stats['energia'] = max(0, $stats['energia'] - 2);
                $stats['felicidad'] = min(100, $stats['felicidad'] + 5);
                $stats['hambre'] = min(100, $stats['hambre'] + 2);
            }
            break;
        case 'reset':
            session_destroy();
            exit;
    }
    
    echo json_encode(['status' => 'ok', 'stats' => $_SESSION['stats']]);
    exit;
}

if (isset($_GET['get_stats'])) {
    header('Content-Type: application/json');
    echo json_encode(['status' => 'ok', 'stats' => $_SESSION['stats']]);
    exit;
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>El Rolo - Mascota Virtual (Premium Edition)</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;700;900&display=swap" rel="stylesheet">
    <style>
        /* ================= ESTILOS BASE ================= */
        :root {
            --glass-bg: rgba(25, 25, 35, 0.45);
            --glass-border: rgba(255, 255, 255, 0.15);
            --glass-shadow: 0 8px 32px 0 rgba(0, 0, 0, 0.37);
        }

        body { 
            font-family: 'Nunito', sans-serif; background-color: #0f0f13; color: white; 
            display: flex; justify-content: center; align-items: center; 
            height: 100vh; margin: 0; overflow: hidden; 
            background-image: radial-gradient(circle at 50% 50%, #1a1a24 0%, #000000 100%);
            user-select: none;
        }
        
        .game-container {
            width: 100%; max-width: 1200px; height: 90vh; min-height: 600px;
            border-radius: 40px; border: 1px solid rgba(255,255,255,0.05);
            position: relative; overflow: hidden;
            box-shadow: 0 30px 60px -10px rgba(0, 0, 0, 0.9), inset 0 0 40px rgba(0,0,0,0.5);
            transition: background 1s ease-in-out;
        }

        /* FONDOS DE HABITACIONES */
        .room-cuarto { background: linear-gradient(135deg, #1e3c72 0%, #2a5298 50%, #fa9c7a 100%); background-size: 200% 200%; animation: gradBG 10s ease infinite; }
        .room-cocina { background: linear-gradient(135deg, #d47a43 0%, #e8b279 50%, #ffdfba 100%); background-size: 200% 200%; animation: gradBG 10s ease infinite; }
        .room-bano { background: linear-gradient(135deg, #00c6ff 0%, #0072ff 50%, #74ebd5 100%); background-size: 200% 200%; animation: gradBG 10s ease infinite; }
        .room-juegos { background: linear-gradient(135deg, #11998e 0%, #38ef7d 50%, #a8e063 100%); background-size: 200% 200%; animation: gradBG 10s ease infinite; }
        @keyframes gradBG { 0% { background-position: 0% 50%; } 50% { background-position: 100% 50%; } 100% { background-position: 0% 50%; } }

        .night-overlay { position: absolute; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(to bottom, rgba(5, 5, 15, 0.9), rgba(15, 15, 30, 0.95)); pointer-events: none; opacity: 0; transition: opacity 1.5s ease; z-index: 5; backdrop-filter: blur(2px); }
        .is-night .night-overlay { opacity: 1; }
        .glass-panel { background: var(--glass-bg); backdrop-filter: blur(16px); -webkit-backdrop-filter: blur(16px); border: 1px solid var(--glass-border); box-shadow: var(--glass-shadow); }

        /* ================= DECORACIÓN COCINA (CSS ART) ================= */
        .bg-cocina-decor { position: absolute; inset: 0; pointer-events: none; z-index: 1; display: none; }
        .room-cocina .bg-cocina-decor { display: block; }
        
        .cocina-pared { position: absolute; top: 0; left: 0; width: 100%; height: 60%; background: linear-gradient(to bottom, rgba(255,255,255,0.1), transparent); border-bottom: 2px solid rgba(0,0,0,0.1); }
        .cocina-mesa { position: absolute; bottom: 0; left: 0; width: 100%; height: 25%; background: linear-gradient(to bottom, #8b5a2b, #5c3a18); border-top: 10px solid #a06b35; box-shadow: inset 0 10px 20px rgba(0,0,0,0.3); }
        .cocina-ventana { position: absolute; top: 10%; left: 50%; transform: translateX(-50%); width: 180px; height: 150px; background: linear-gradient(to bottom, #87CEEB, #E0F6FF); border: 8px solid #fff; border-radius: 10px; box-shadow: inset 0 0 20px rgba(0,0,0,0.2), 0 10px 15px rgba(0,0,0,0.2); }
        .cocina-ventana::before { content: ''; position: absolute; top: 50%; left: 0; width: 100%; height: 6px; background: #fff; transform: translateY(-50%); }
        .cocina-ventana::after { content: ''; position: absolute; top: 0; left: 50%; width: 6px; height: 100%; background: #fff; transform: translateX(-50%); }
        .cocina-nevera { position: absolute; left: 8%; bottom: 20%; width: 140px; height: 280px; background: linear-gradient(to right, #e0e0e0, #ffffff); border-radius: 15px 15px 5px 5px; border: 2px solid #ccc; box-shadow: 15px 15px 25px rgba(0,0,0,0.3); }
        .cocina-nevera::before { content: ''; position: absolute; top: 35%; left: 0; width: 100%; height: 4px; background: #bbb; } /* División puertas */
        .cocina-nevera-manija1 { position: absolute; top: 10%; right: 15px; width: 6px; height: 40px; background: #999; border-radius: 3px; }
        .cocina-nevera-manija2 { position: absolute; top: 40%; right: 15px; width: 6px; height: 60px; background: #999; border-radius: 3px; }
        .cocina-fogon { position: absolute; right: 8%; bottom: 20%; width: 160px; height: 180px; background: linear-gradient(to bottom, #444, #222); border-radius: 10px 10px 5px 5px; border: 2px solid #111; box-shadow: -15px 15px 25px rgba(0,0,0,0.3); }
        .cocina-fogon-top { position: absolute; top: 0; left: 0; width: 100%; height: 20px; background: #111; border-radius: 10px 10px 0 0; display: flex; justify-content: space-around; align-items: center; }
        .cocina-hornilla { width: 30px; height: 10px; background: #555; border-radius: 50%; box-shadow: inset 0 -2px 5px #000; }

        /* ================= LA MASCOTA ================= */
        .mascota-wrapper { position: relative; z-index: 10; display: flex; justify-content: center; align-items: center;}
        .mascota {
            width: 190px; height: 160px; background: radial-gradient(circle at 30% 30%, #e0a365, #b87b40); border-radius: 40% 60% 70% 30% / 40% 50% 60% 50%;
            position: relative; box-shadow: inset -20px -20px 30px rgba(0,0,0,0.4), inset 10px 10px 20px rgba(255,255,255,0.3), 15px 25px 25px rgba(0,0,0,0.4);
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1); display: flex; justify-content: center; align-items: center; flex-direction: column; animation: breathe 4s infinite ease-in-out;
        }
        @keyframes breathe { 0%, 100% { transform: scale(1) translateY(0); } 50% { transform: scale(1.02, 0.98) translateY(-5px); } }
        .ojos { display: flex; gap: 24px; margin-top: -35px; transition: all 0.3s;}
        .ojo { width: 28px; height: 32px; background: white; border-radius: 50%; position: relative; overflow: hidden; box-shadow: inset 0 3px 5px rgba(0,0,0,0.3);}
        .pupila { width: 14px; height: 16px; background: #1a1a1a; border-radius: 50%; position: absolute; top: 8px; right: 6px; transition: all 0.1s; }
        .pupila::after { content: ''; position: absolute; top: 2px; left: 2px; width: 4px; height: 4px; background: white; border-radius: 50%;} 
        .boca { width: 40px; height: 15px; border-bottom: 6px solid #2a1608; border-radius: 0 0 50% 50%; margin-top: 18px; transition: all 0.2s;}
        
        .durmiendo { transform: scaleY(0.8) translateY(30px); background: radial-gradient(circle at 30% 30%, #9c7145, #7a5027); animation: breathe-sleep 4s infinite ease-in-out;}
        @keyframes breathe-sleep { 0%, 100% { transform: scaleY(0.8) translateY(30px); } 50% { transform: scaleY(0.85) translateY(25px); } }
        .durmiendo .ojo { height: 6px; border-radius: 10px; background: #111; margin-top: 15px; border: none; box-shadow: none; }
        .durmiendo .pupila { display: none; }
        .durmiendo .boca { width: 15px; height: 15px; border-bottom: none; border: 4px solid #111; border-radius: 50%; margin-top: 25px; animation: roncar 4s infinite;}
        @keyframes roncar { 0%, 100%{ transform: scale(1); } 50%{ transform: scale(1.5); } }
        
        .triste .boca { border-bottom: none; border-top: 6px solid #2a1608; border-radius: 50% 50% 0 0; margin-top: 25px; }
        .sucio { filter: sepia(0.5) hue-rotate(-30deg) brightness(0.8); }
        .enjabonado::after { content: ''; position: absolute; top:0; left:0; right:0; bottom:0; background: rgba(255,255,255,0.6); border-radius: inherit; z-index: 1;}
        
        .comiendo .boca { height: 40px; width: 40px; border: none; background-color: #381503; border-radius: 50%; margin-top: 5px; animation: masticar 0.4s infinite; }
        .comiendo { animation: salto 0.3s ease; }
        @keyframes masticar { 0%, 100% { height: 40px; width: 40px; } 50% { height: 15px; width: 45px; } }
        @keyframes salto { 0%, 100% { transform: translateY(0); } 50% { transform: translateY(-30px) scale(1.05); } }

        /* ================= PARTÍCULAS Y MIGAJAS ================= */
        .particula-miga { position: absolute; width: 8px; height: 8px; border-radius: 2px; pointer-events: none; z-index: 30; animation: caer-miga 0.8s cubic-bezier(0.25, 0.46, 0.45, 0.94) forwards;}
        @keyframes caer-miga { 0% { transform: translateY(0) scale(1) rotate(0deg); opacity: 1; } 100% { transform: translateY(80px) scale(0) rotate(180deg); opacity: 0; } }

        /* ================= MODALES Y UI ================= */
        .modal-overlay { position: absolute; inset: 0; background: rgba(0,0,0,0.8); backdrop-filter: blur(5px); z-index: 50; display: none; justify-content: center; align-items: center; opacity: 0; transition: opacity 0.3s;}
        .modal-overlay.active { display: flex; opacity: 1; }
        .modal-content { background: linear-gradient(135deg, #2a2a35, #1a1a24); border: 2px solid #ffcc00; border-radius: 20px; width: 90%; max-width: 600px; max-height: 80vh; overflow-y: auto; padding: 20px; box-shadow: 0 20px 50px rgba(0,0,0,0.8); position: relative; transform: scale(0.9); transition: transform 0.3s;}
        .modal-overlay.active .modal-content { transform: scale(1); }
        .btn-cerrar { position: absolute; top: 15px; right: 15px; background: #ff4b2b; color: white; width: 30px; height: 30px; border-radius: 50%; font-weight: bold; cursor: pointer; border: none; box-shadow: 0 4px 6px rgba(0,0,0,0.3); }

        .shop-item { background: rgba(255,255,255,0.05); border: 1px solid rgba(255,255,255,0.1); border-radius: 15px; padding: 15px; display: flex; flex-direction: column; align-items: center; transition: all 0.2s; }
        .shop-item:hover { background: rgba(255,255,255,0.1); transform: translateY(-5px); box-shadow: 0 10px 20px rgba(0,0,0,0.3); }
        .btn-comprar { background: linear-gradient(to right, #f59e0b, #d97706); color: white; font-weight: bold; padding: 5px 15px; border-radius: 20px; border: none; cursor: pointer; margin-top: 10px; width: 100%; box-shadow: 0 4px 6px rgba(0,0,0,0.2); }
        .btn-comprar:active { transform: scale(0.95); }
        .btn-comprar:disabled { background: #555; color: #888; cursor: not-allowed; }

        /* ================= DRAG AND DROP & INVENTARIO ================= */
        .inventario-scroll { display: flex; overflow-x: auto; gap: 20px; padding: 10px 20px; width: 100%; align-items: center; scroll-behavior: smooth;}
        .inventario-scroll::-webkit-scrollbar { height: 8px; }
        .inventario-scroll::-webkit-scrollbar-thumb { background: rgba(255,255,255,0.3); border-radius: 4px; }
        
        .draggable { cursor: grab; touch-action: none; position: relative; z-index: 30; transition: transform 0.2s;}
        .draggable:active { cursor: grabbing; transform: scale(1.1); }
        .dragging-clone { position: fixed; z-index: 9999; pointer-events: none; filter: drop-shadow(0 15px 15px rgba(0,0,0,0.5)); margin:0; }
        .item-empty { filter: grayscale(1) opacity(0.3); pointer-events: none; }
        .item-badge { position: absolute; top: -10px; right: -10px; background: #ff4b2b; color: white; font-size: 12px; font-weight: bold; width: 24px; height: 24px; border-radius: 50%; display: flex; justify-content: center; align-items: center; border: 2px solid #fff; box-shadow: 0 2px 5px rgba(0,0,0,0.4); z-index: 5;}

        /* ================= COMIDAS (CSS ART) ================= */
        .art-empanada { width: 70px; height: 35px; background: linear-gradient(to bottom, #f0a522, #c77a10); border-radius: 35px 35px 0 0; border: 2px solid #8f5403; border-bottom: 6px solid #9c5d05; position: relative; display: inline-block; box-shadow: inset 0 -4px 8px rgba(0,0,0,0.2);}
        .art-changua { width: 70px; height: 40px; background: linear-gradient(to bottom, #ffffff, #dcdcdc); border-radius: 5px 5px 35px 35px; border: 3px solid #a3a3a3; position: relative; display: inline-block; box-shadow: inset 0 -5px 15px rgba(0,0,0,0.1);}
        .art-changua::before { content: ''; position: absolute; top: -12px; left: 2px; width: 60px; height: 16px; background: #f0f7f2; border-radius: 50%; border: 2px solid #ccc;} 
        .art-changua::after { content: ''; position: absolute; top: -8px; left: 15px; width: 18px; height: 18px; background: white; border-radius: 50%; box-shadow: 10px 0 0 white;} 
        
        .art-sancocho { width: 80px; height: 45px; background: linear-gradient(to bottom, #888, #555); border-radius: 5px 5px 40px 40px; border: 3px solid #333; position: relative; display: inline-block; box-shadow: inset 0 -5px 15px rgba(0,0,0,0.4);}
        .art-sancocho::before { content: ''; position: absolute; top: -14px; left: 2px; width: 70px; height: 20px; background: radial-gradient(ellipse, #e8c92a, #b39810); border-radius: 50%; border: 2px solid #444;}
        .art-sancocho::after { content: ''; position: absolute; top: -20px; left: 25px; width: 15px; height: 30px; background: #dcdcdc; border-radius: 5px; transform: rotate(15deg); border: 1px solid #aaa; z-index: 2;} /* Hueso */
        
        .art-ajiaco { width: 75px; height: 40px; background: linear-gradient(to bottom, #9c5835, #5e311a); border-radius: 5px 5px 35px 35px; border: 3px solid #3d1f0f; position: relative; display: inline-block;}
        .art-ajiaco::before { content: ''; position: absolute; top: -12px; left: 2px; width: 65px; height: 18px; background: radial-gradient(ellipse, #d6ba38, #a68f24); border-radius: 50%; border: 2px solid #63361f;}
        .art-ajiaco::after { content: ''; position: absolute; top: -8px; left: 20px; width: 25px; height: 10px; background: #fff; border-radius: 50%; filter: blur(1px); z-index: 2;} /* Crema */

        .art-arepa_rellena { width: 65px; height: 65px; background: radial-gradient(circle, #fce079, #d6b02b); border-radius: 50%; border: 3px solid #b38e14; position: relative; display: inline-block; box-shadow: inset -5px -5px 10px rgba(0,0,0,0.2);}
        .art-arepa_rellena::after { content: ''; position: absolute; top: 10px; left: -5px; width: 30px; height: 45px; background: #6b3313; border-radius: 50%; border-left: 4px solid #fff; box-shadow: 2px 0 0 #ffcc00; z-index: 2;} /* Relleno asomando */

        .art-chorizo { width: 70px; height: 25px; background: linear-gradient(to bottom, #bd3d2a, #7a2215); border-radius: 15px; border: 2px solid #521309; position: relative; display: inline-block; transform: rotate(-10deg);}
        .art-chorizo::before { content: ''; position: absolute; top: 0; left: 15px; width: 2px; height: 100%; background: #521309; box-shadow: 15px 0 0 #521309, 30px 0 0 #521309;} /* Marcas */
        .art-chorizo::after { content: ''; position: absolute; top: 15px; left: 40px; width: 30px; height: 30px; background: #fce079; border-radius: 50%; border: 2px solid #b38e14; z-index: -1;} /* Arepita */

        .art-bonbonbum { width: 35px; height: 35px; background: radial-gradient(circle at 30% 30%, #ff4b4b, #b30000); border-radius: 50%; position: relative; display: inline-block; border: 1px solid #800000; margin-bottom: 20px;}
        .art-bonbonbum::after { content: ''; position: absolute; bottom: -25px; left: 15px; width: 4px; height: 25px; background: #fff; border-radius: 2px; border: 1px solid #ccc;}

        .art-nucita { width: 40px; height: 40px; background: #fff; border-radius: 5px 5px 20px 20px; border: 2px solid #ccc; position: relative; display: inline-block;}
        .art-nucita::before { content: ''; position: absolute; top: -5px; left: -2px; width: 40px; height: 15px; background: linear-gradient(to right, #fff 50%, #6b4021 50%); border-radius: 50%; border: 2px solid #999;}
        
        .art-jabon { width: 60px; height: 35px; background: linear-gradient(135deg, #3b71ff, #003dd9); border-radius: 8px; border: 2px solid #002ba1; display:flex; justify-content:center; align-items:center; color:white; font-size:12px; font-weight:bold; letter-spacing: 1px;}
        .art-regadera { width: 45px; height: 45px; position: relative; display: inline-block; transform: rotate(15deg);}
        .regadera-mango { width: 10px; height: 35px; background: #ccc; border-radius: 5px; position: absolute; bottom: 0; left: 17px; border: 1px solid #777;}
        .regadera-cabeza { width: 45px; height: 12px; background: #aaa; border-radius: 20px 20px 5px 5px; position: absolute; top: 0; left: 0; border: 1px solid #777;}
        
        /* Pelota */
        .art-pelota { width: 50px; height: 50px; background: radial-gradient(circle at 30% 30%, #ff4b2b, #8e0e00); border-radius: 50%; position: absolute; z-index: 15; cursor: grab; display: none; box-shadow: inset -5px -5px 10px rgba(0,0,0,0.5), 5px 5px 15px rgba(0,0,0,0.4); border: 2px solid #520600; }

    </style>
</head>
<body>

<div id="app" class="game-container room-cuarto flex flex-col">
    <!-- Fondos dinámicos y superposiciones -->
    <div class="night-overlay"></div>
    <div class="bg-cocina-decor">
        <div class="cocina-ventana"></div>
        <div class="cocina-pared"></div>
        <div class="cocina-mesa"></div>
        <div class="cocina-nevera">
            <div class="cocina-nevera-manija1"></div>
            <div class="cocina-nevera-manija2"></div>
        </div>
        <div class="cocina-fogon">
            <div class="cocina-fogon-top">
                <div class="cocina-hornilla"></div><div class="cocina-hornilla"></div>
            </div>
        </div>
    </div>

    <!-- UI ALERTA -->
    <div id="alert-msg" class="absolute top-[20%] left-1/2 transform -translate-x-1/2 bg-red-600 text-white px-6 py-2 rounded-full font-bold shadow-xl z-50 opacity-0 transition-opacity duration-300 pointer-events-none">Alerta</div>
    
    <!-- TOP BAR -->
    <div class="h-[18%] px-8 py-2 z-20 relative flex flex-col justify-center bg-black/20">
        <div class="flex justify-between items-center mb-3">
            <div class="flex items-center gap-4">
                <h1 class="font-black text-3xl tracking-wider text-white drop-shadow-md">EL ROLO</h1>
                <span id="room-name" class="glass-panel px-5 py-1.5 rounded-full text-xs font-bold uppercase tracking-widest text-white">Dormitorio</span>
            </div>
            <div class="flex items-center gap-2 bg-gradient-to-r from-yellow-500 to-amber-600 px-4 py-1.5 rounded-full border-2 border-yellow-300 shadow-lg cursor-pointer hover:scale-105 transition-transform" onclick="abrirTienda()">
                <div class="w-5 h-5 bg-yellow-300 rounded-full border-2 border-white shadow-inner flex items-center justify-center font-bold text-yellow-600 text-[10px]">$</div>
                <span id="txt-monedas" class="font-black text-lg text-white drop-shadow-md">0</span>
                <span class="text-xs ml-1 uppercase font-bold text-yellow-100 hidden sm:inline">Tienda</span>
            </div>
        </div>

        <div class="grid grid-cols-4 gap-4">
            <div class="bg-black/40 p-2 rounded-xl border border-white/5 shadow-inner">
                <div class="flex justify-between text-red-300 font-bold text-[10px] uppercase"><span>Hambre</span> <span id="txt-hambre">0%</span></div>
                <div class="h-2.5 rounded-full bg-black/60 mt-1 overflow-hidden border border-white/10"><div id="bar-hambre" class="h-full bg-gradient-to-r from-red-600 to-red-400 transition-all duration-500 w-1/2 relative"><div class="absolute top-0 left-0 right-0 h-1 bg-white/30"></div></div></div>
            </div>
            <div class="bg-black/40 p-2 rounded-xl border border-white/5 shadow-inner">
                <div class="flex justify-between text-yellow-300 font-bold text-[10px] uppercase"><span>Energía</span> <span id="txt-energia">100%</span></div>
                <div class="h-2.5 rounded-full bg-black/60 mt-1 overflow-hidden border border-white/10"><div id="bar-energia" class="h-full bg-gradient-to-r from-yellow-600 to-yellow-400 transition-all duration-500 w-full relative"><div class="absolute top-0 left-0 right-0 h-1 bg-white/30"></div></div></div>
            </div>
            <div class="bg-black/40 p-2 rounded-xl border border-white/5 shadow-inner">
                <div class="flex justify-between text-green-300 font-bold text-[10px] uppercase"><span>Felicidad</span> <span id="txt-felicidad">50%</span></div>
                <div class="h-2.5 rounded-full bg-black/60 mt-1 overflow-hidden border border-white/10"><div id="bar-felicidad" class="h-full bg-gradient-to-r from-green-600 to-green-400 transition-all duration-500 w-1/2 relative"><div class="absolute top-0 left-0 right-0 h-1 bg-white/30"></div></div></div>
            </div>
            <div class="bg-black/40 p-2 rounded-xl border border-white/5 shadow-inner">
                <div class="flex justify-between text-orange-300 font-bold text-[10px] uppercase"><span>Suciedad</span> <span id="txt-suciedad">0%</span></div>
                <div class="h-2.5 rounded-full bg-black/60 mt-1 overflow-hidden border border-white/10"><div id="bar-suciedad" class="h-full bg-gradient-to-r from-orange-700 to-amber-500 transition-all duration-500 w-0 relative"><div class="absolute top-0 left-0 right-0 h-1 bg-white/30"></div></div></div>
            </div>
        </div>
    </div>

    <!-- MIDDLE: Escenario -->
    <div class="h-[57%] flex justify-center items-center relative z-10 w-full" id="escenario">
        <button class="absolute left-6 top-1/2 -translate-y-1/2 bg-white/10 hover:bg-white/20 border border-white/20 w-12 h-12 rounded-full text-white text-xl z-20 backdrop-blur-md transition-all" onclick="cambiarHabitacion(-1)">❮</button>
        <button class="absolute right-6 top-1/2 -translate-y-1/2 bg-white/10 hover:bg-white/20 border border-white/20 w-12 h-12 rounded-full text-white text-xl z-20 backdrop-blur-md transition-all" onclick="cambiarHabitacion(1)">❯</button>

        <div class="mascota-wrapper">
            <div id="mascota" class="mascota">
                <div class="absolute -top-16 -right-5 hidden z-10 font-black text-indigo-200 text-shadow-md is-night:block">
                    <div class="animate-[floatZ_3s_infinite_ease-in] text-lg opacity-0">z</div>
                    <div class="animate-[floatZ_3s_infinite_ease-in_1s] text-2xl ml-4 -mt-4 opacity-0">Z</div>
                    <div class="animate-[floatZ_3s_infinite_ease-in_2s] text-4xl ml-10 -mt-6 opacity-0">Z</div>
                </div>
                <div class="ojos"><div class="ojo"><div class="pupila"></div></div><div class="ojo"><div class="pupila"></div></div></div>
                <div class="boca"></div>
                <div id="foam-container" class="absolute inset-0 pointer-events-none rounded-[inherit] overflow-hidden"></div>
            </div>
        </div>
        <div id="pelota-juego" class="art-pelota"></div>
    </div>

    <!-- BOTTOM: Controles y Nevera -->
    <div class="h-[25%] z-20 relative flex flex-col justify-center items-center glass-panel border-b-0 border-l-0 border-r-0 rounded-t-[40px]">
        
        <!-- Cuarto -->
        <div id="ctrl-cuarto" class="room-controls flex w-full justify-center gap-16 items-center h-full">
            <div class="relative flex flex-col items-center group cursor-pointer hover:-translate-y-1 transition-transform" onclick="toggleDormir()">
                <div class="w-10 h-14 bg-gradient-to-b from-white to-gray-300 rounded-lg border-2 border-gray-400 relative shadow-lg">
                    <div class="w-6 h-6 bg-gray-100 rounded absolute top-1.5 left-1.5 border-b-4 border-gray-400 is-night:top-5 is-night:border-b-0 is-night:border-t-4 transition-all"></div>
                </div>
                <span class="mt-3 text-xs font-bold uppercase text-white/80">Luz</span>
            </div>
            <div class="relative flex flex-col items-center cursor-pointer opacity-60 hover:opacity-100 transition-opacity ml-12" onclick="ejecutarAccion('reset')">
                <div class="text-xs font-bold border border-white/20 rounded-xl px-5 py-2 bg-red-900/40 uppercase tracking-widest shadow-lg">Reiniciar Partida</div>
            </div>
        </div>

        <!-- Cocina (Inventario Dinámico) -->
        <div id="ctrl-cocina" class="room-controls hidden w-full h-full relative">
            <div class="absolute -top-8 w-full text-center text-[10px] font-bold text-white/50 uppercase tracking-widest">La Nevera (Arrastra a la boca)</div>
            <div class="inventario-scroll" id="contenedor-comida">
                <!-- Se llena con JS -->
            </div>
        </div>

        <!-- Baño -->
        <div id="ctrl-bano" class="room-controls hidden w-full justify-center gap-24 items-center h-full">
            <div class="relative flex flex-col items-center">
                <div class="draggable art-jabon" data-action="tool_jabon" draggable="false">REY</div>
                <span class="mt-4 text-xs font-bold text-blue-200">1. Jabón</span>
            </div>
            <div class="relative flex flex-col items-center">
                <div class="draggable art-regadera" data-action="tool_regadera" draggable="false">
                    <div class="regadera-mango"></div><div class="regadera-cabeza"></div>
                </div>
                <span class="mt-4 text-xs font-bold text-cyan-200">2. Enjuagar</span>
            </div>
        </div>

        <!-- Juegos -->
        <div id="ctrl-juegos" class="room-controls hidden w-full flex flex-col justify-center items-center h-full pt-2">
            <div class="flex justify-center gap-16 w-full">
                <div class="relative flex flex-col items-center cursor-pointer hover:scale-110 transition-transform" onclick="intentarJugarTM()">
                    <div class="w-24 h-10 bg-gradient-to-b from-red-600 to-red-800 rounded-lg border-b-4 border-red-900 relative shadow-lg">
                        <div class="absolute top-2 left-2 right-6 h-3 bg-black rounded-sm border-x-[16px] border-black"></div>
                        <div class="absolute -bottom-2 left-3 w-4 h-4 bg-black rounded-full border-2 border-gray-400"></div>
                        <div class="absolute -bottom-2 right-3 w-4 h-4 bg-black rounded-full border-2 border-gray-400"></div>
                    </div>
                    <span class="mt-4 text-xs font-bold text-red-200">TransMilenio (-25⚡)</span>
                </div>
            </div>
            <p class="absolute bottom-3 text-[10px] text-green-300 font-bold uppercase tracking-widest">Lanza la pelota para ganar monedas</p>
        </div>
    </div>
</div>

<!-- ================= MODAL TIENDA ================= -->
<div id="modal-tienda" class="modal-overlay">
    <div class="modal-content">
        <button class="btn-cerrar" onclick="cerrarTienda()">X</button>
        <h2 class="text-2xl font-black text-center mb-1 text-yellow-400">Tienda de Doña Gloria</h2>
        <p class="text-center text-sm text-gray-300 mb-6">Compra platillos típicos para llenar a tu mascota. Tienes <strong id="tienda-monedas" class="text-yellow-400">0</strong> monedas.</p>
        
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4" id="tienda-grid">
            <!-- Rellenado por JS -->
        </div>
    </div>
</div>

<script>
    // --- ESTADO Y DB DE JS ---
    let localStats = { inventario: {} };
    let sleepInterval = null;
    let soapLevel = 0; 

    const comidasDB = {
        'empanada': { nombre: 'Empanada', precio: 5, hambre: 20, artClass: 'art-empanada', color: '#d8961c' },
        'changua': { nombre: 'Changua', precio: 10, hambre: 30, artClass: 'art-changua', color: '#ffffff' },
        'arepa_rellena': { nombre: 'Arepa Rellena', precio: 18, hambre: 45, artClass: 'art-arepa_rellena', color: '#e6b853' },
        'sancocho': { nombre: 'Sancocho', precio: 25, hambre: 60, artClass: 'art-sancocho', color: '#e8c92a' },
        'ajiaco': { nombre: 'Ajiaco', precio: 30, hambre: 65, artClass: 'art-ajiaco', color: '#d6ba38' },
        'chorizo': { nombre: 'Chorizo c/ Arepa', precio: 15, hambre: 35, artClass: 'art-chorizo', color: '#a32b1d' },
        'bonbonbum': { nombre: 'Bon Bon Bum', precio: 3, hambre: 5, artClass: 'art-bonbonbum', color: '#e61919' },
        'nucita': { nombre: 'Nucita', precio: 5, hambre: 10, artClass: 'art-nucita', color: '#6b4021' }
    };
    
    // NAVEGACIÓN
    const habitaciones = [{id: 'cuarto', name: 'Dormitorio'}, {id: 'cocina', name: 'Cocina'}, {id: 'bano', name: 'Baño'}, {id: 'juegos', name: 'Juegos'}];
    let habitacionActual = 0;
    const app = document.getElementById('app');
    const mascotaDiv = document.getElementById('mascota');
    const alertToast = document.getElementById('alert-msg');
    const escenario = document.getElementById('escenario');
    
    function cambiarHabitacion(direccion) {
        document.getElementById(`ctrl-${habitaciones[habitacionActual].id}`).classList.add('hidden');
        app.classList.remove(`room-${habitaciones[habitacionActual].id}`);
        habitacionActual = (habitacionActual + direccion + habitaciones.length) % habitaciones.length;
        const nuevaHab = habitaciones[habitacionActual];
        
        document.getElementById(`ctrl-${nuevaHab.id}`).classList.remove('hidden');
        app.classList.add(`room-${nuevaHab.id}`);
        document.getElementById('room-name').innerText = nuevaHab.name;

        if(app.classList.contains('is-night')) toggleDormir(); 

        const pelotaEl = document.getElementById('pelota-juego');
        if (nuevaHab.id === 'juegos') {
            pelotaEl.style.display = 'block'; resetPelota();
        } else {
            pelotaEl.style.display = 'none';
        }
    }

    function mostrarAlerta(msg) {
        alertToast.innerText = msg;
        alertToast.style.opacity = '1';
        setTimeout(() => alertToast.style.opacity = '0', 2500);
    }

    // --- RENDERIZADO DE UI ---
    function actualizarUI(stats) {
        localStats = stats;
        document.getElementById('bar-hambre').style.width = stats.hambre + '%'; document.getElementById('txt-hambre').innerText = stats.hambre + '%';
        document.getElementById('bar-energia').style.width = stats.energia + '%'; document.getElementById('txt-energia').innerText = stats.energia + '%';
        document.getElementById('bar-felicidad').style.width = stats.felicidad + '%'; document.getElementById('txt-felicidad').innerText = stats.felicidad + '%';
        document.getElementById('bar-suciedad').style.width = stats.suciedad + '%'; document.getElementById('txt-suciedad').innerText = stats.suciedad + '%';
        document.getElementById('txt-monedas').innerText = stats.monedas;
        document.getElementById('tienda-monedas').innerText = stats.monedas;

        mascotaDiv.classList.remove('sucio', 'triste');
        if (stats.suciedad > 50) mascotaDiv.classList.add('sucio');
        if (stats.felicidad < 30 || stats.hambre > 70) mascotaDiv.classList.add('triste');

        renderInventario();
        renderTienda();
    }

    function renderInventario() {
        const contenedor = document.getElementById('contenedor-comida');
        contenedor.innerHTML = ''; // Limpiar
        
        // Convertir objeto inventario a array para ordenarlo (opcional)
        for (let key in localStats.inventario) {
            let cantidad = localStats.inventario[key];
            let itemData = comidasDB[key];
            let vacio = cantidad === 0;

            let div = document.createElement('div');
            div.className = `flex flex-col items-center shrink-0 w-24 relative ${vacio ? 'item-empty' : ''}`;
            
            // Etiqueta de cantidad
            let badge = document.createElement('div');
            badge.className = 'item-badge';
            badge.innerText = cantidad;
            div.appendChild(badge);

            // Arte CSS del item
            let art = document.createElement('div');
            art.className = `${itemData.artClass} draggable`;
            art.dataset.action = `comer_${key}`;
            art.dataset.color = itemData.color;
            art.draggable = false;
            div.appendChild(art);

            // Nombre
            let nombre = document.createElement('span');
            nombre.className = 'mt-3 text-[10px] font-bold text-white/80 uppercase text-center';
            nombre.innerText = itemData.nombre;
            div.appendChild(nombre);

            contenedor.appendChild(div);
        }
        
        // Re-asignar eventos de drag a los nuevos elementos
        document.querySelectorAll('.draggable').forEach(el => {
            el.addEventListener('mousedown', startDrag);
            el.addEventListener('touchstart', startDrag, {passive: false});
        });
    }

    // --- TIENDA ---
    function abrirTienda() { document.getElementById('modal-tienda').classList.add('active'); }
    function cerrarTienda() { document.getElementById('modal-tienda').classList.remove('active'); }

    function renderTienda() {
        const grid = document.getElementById('tienda-grid');
        grid.innerHTML = '';
        for (let key in comidasDB) {
            let item = comidasDB[key];
            let div = document.createElement('div');
            div.className = 'shop-item';
            div.innerHTML = `
                <div class="h-16 flex items-center justify-center transform scale-75"><div class="${item.artClass}"></div></div>
                <h4 class="text-xs font-bold mt-2 text-center text-white h-8">${item.nombre}</h4>
                <p class="text-[10px] text-green-400 mb-2 font-bold">- ${item.hambre}% Hambre</p>
                <button class="btn-comprar flex items-center justify-center gap-1" ${localStats.monedas < item.precio ? 'disabled' : ''} onclick="ejecutarAccion('comprar_${key}')">
                    <span class="text-[10px]">$</span>${item.precio}
                </button>
            `;
            grid.appendChild(div);
        }
    }

    // --- COMUNICACIÓN PHP ---
    function ejecutarAccion(accion) {
        if (accion.includes('comer_')) {
            mascotaDiv.classList.add('comiendo');
            setTimeout(() => mascotaDiv.classList.remove('comiendo'), 600);
        } else if (accion === 'jugar_tm') {
            mascotaDiv.style.transform = "scale(1.2) rotate(360deg)";
            setTimeout(() => mascotaDiv.style.transform = "", 500);
        }

        fetch(`index.php?action=${accion}`)
            .then(res => res.json())
            .then(data => {
                if(data.status === 'error') {
                    mostrarAlerta(data.msg);
                } else {
                    actualizarUI(data.stats);
                }
            });
    }

    function intentarJugarTM() {
        if (localStats.energia < 25) { mostrarAlerta("¡Muy cansado para jugar!"); return; }
        ejecutarAccion('jugar_tm');
    }

    function toggleDormir() {
        app.classList.toggle('is-night');
        mascotaDiv.classList.toggle('durmiendo');
        if(app.classList.contains('is-night')){
            sleepInterval = setInterval(() => {
                fetch(`index.php?action=sleep_tick`).then(res => res.json()).then(data => {
                    actualizarUI(data.stats);
                    if (data.stats.energia >= 100 && app.classList.contains('is-night')) toggleDormir();
                });
            }, 2000); 
        } else { clearInterval(sleepInterval); }
    }

    // --- SISTEMA DRAG AND DROP MEJORADO ---
    let draggedElement = null;
    let clone = null;
    let lastToolX = 0, lastToolY = 0;

    function startDrag(e) {
        if(app.classList.contains('is-night')) return; 
        if(e.target.closest('.item-empty')) return; // No arrastrar vacíos
        
        e.preventDefault(); 
        draggedElement = e.target.closest('.draggable');
        clone = draggedElement.cloneNode(true);
        clone.classList.add('dragging-clone');
        
        const rect = draggedElement.getBoundingClientRect();
        clone.style.width = rect.width + 'px';
        clone.style.height = rect.height + 'px';
        
        document.body.appendChild(clone);
        moveClone(e);

        document.addEventListener('mousemove', onDrag);
        document.addEventListener('touchmove', onDrag, {passive: false});
        document.addEventListener('mouseup', stopDrag);
        document.addEventListener('touchend', stopDrag);
    }

    function moveClone(e) {
        let clientX = e.type.includes('mouse') ? e.clientX : e.touches[0].clientX;
        let clientY = e.type.includes('mouse') ? e.clientY : e.touches[0].clientY;
        
        clone.style.left = (clientX - clone.offsetWidth / 2) + 'px';
        clone.style.top = (clientY - clone.offsetHeight / 2) + 'px';

        let action = draggedElement.dataset.action;
        if (action === 'tool_jabon' || action === 'tool_regadera') {
            let dist = Math.hypot(clientX - lastToolX, clientY - lastToolY);
            if (dist > 10) {
                lastToolX = clientX; lastToolY = clientY;
                checkToolCollision(clientX, clientY, action);
            }
        }
    }

    function onDrag(e) { e.preventDefault(); moveClone(e); }

    function stopDrag(e) {
        document.removeEventListener('mousemove', onDrag);
        document.removeEventListener('touchmove', onDrag);
        document.removeEventListener('mouseup', stopDrag);
        document.removeEventListener('touchend', stopDrag);
        
        let clientX = e.type.includes('mouse') ? e.clientX : e.changedTouches[0].clientX;
        let clientY = e.type.includes('mouse') ? e.clientY : e.changedTouches[0].clientY;
        
        // HITBOX MEJORADA: Centro de la boca
        const rect = mascotaDiv.getBoundingClientRect();
        const centroX = rect.left + rect.width / 2;
        const centroY = rect.top + rect.height / 2 + 20; // Un poco abajo (boca)
        const distancia = Math.hypot(clientX - centroX, clientY - centroY);

        let action = draggedElement.dataset.action;
        
        // Si lo suelta a menos de 90px del centro, come
        if (action.includes('comer_') && distancia < 90) {
            let itemKey = action.replace('comer_', '');
            if(localStats.inventario[itemKey] > 0) {
                ejecutarAccion(action);
                let colorCrumbs = draggedElement.dataset.color || '#ffcc00';
                spawnCrumbs(clientX, clientY, colorCrumbs);
            }
        }
        
        if(clone) clone.remove();
        clone = null; draggedElement = null;
    }

    // --- ANIMACIONES: MIGAJAS Y BAÑO ---
    function spawnCrumbs(x, y, color) {
        for(let i=0; i<8; i++){
            let m = document.createElement('div');
            m.className = 'particula-miga';
            m.style.background = color;
            m.style.left = (x + (Math.random()*40-20)) + 'px';
            m.style.top = y + 'px';
            // Variar duración de la animación un poco
            m.style.animationDuration = (0.6 + Math.random()*0.4) + 's';
            document.body.appendChild(m);
            setTimeout(() => m.remove(), 1000);
        }
    }

    function checkToolCollision(x, y, toolType) {
        const rect = mascotaDiv.getBoundingClientRect();
        if (x >= rect.left - 20 && x <= rect.right + 20 && y >= rect.top - 20 && y <= rect.bottom + 20) {
            if (toolType === 'tool_jabon' && localStats.suciedad > 0) {
                spawnFoam(x, y); soapLevel++;
                if(soapLevel > 20) mascotaDiv.classList.add('enjabonado');
            } 
            else if (toolType === 'tool_regadera') {
                spawnWater(x, y);
                if (soapLevel > 0) {
                    soapLevel -= 2; removeFoam();
                    if (soapLevel <= 0) {
                        mascotaDiv.classList.remove('enjabonado');
                        if(localStats.suciedad > 0) {
                            spawnCoinExplosion(rect.left + rect.width/2, rect.top);
                            ejecutarAccion('ducha_completada');
                        }
                    }
                }
            }
        }
    }

    const foamContainer = document.getElementById('foam-container');
    function spawnFoam(x, y) {
        let b = document.createElement('div'); b.className = 'absolute bg-white/80 rounded-full border border-white shadow-inner pointer-events-none z-20';
        let size = Math.random() * 20 + 10; b.style.width = size + 'px'; b.style.height = size + 'px';
        const rect = mascotaDiv.getBoundingClientRect();
        b.style.left = (x - rect.left - size/2 + (Math.random()*20-10)) + 'px'; b.style.top = (y - rect.top - size/2 + (Math.random()*20-10)) + 'px';
        foamContainer.appendChild(b); if(foamContainer.children.length > 30) foamContainer.firstChild.remove();
    }
    function removeFoam() { if(foamContainer.lastChild) { foamContainer.lastChild.style.opacity = '0'; setTimeout(() => { if(foamContainer.lastChild) foamContainer.lastChild.remove(); }, 200); } }
    function spawnWater(x, y) {
        let w = document.createElement('div'); w.className = 'absolute w-1.5 h-4 bg-blue-400 rounded-full opacity-80 pointer-events-none z-25 animate-[caer_0.4s_linear_forwards]';
        w.style.left = (x - 3) + 'px'; w.style.top = (y + 20) + 'px'; document.body.appendChild(w); setTimeout(() => w.remove(), 400);
    }
    function spawnCoinExplosion(x, y) {
        let uiTarget = document.getElementById('txt-monedas').getBoundingClientRect();
        for(let i=0; i<6; i++){
            let c = document.createElement('div'); c.className = 'absolute w-6 h-6 bg-yellow-400 rounded-full border-2 border-yellow-600 font-bold text-yellow-700 text-[10px] flex justify-center items-center z-[100]'; c.innerText='$';
            c.style.left = x + 'px'; c.style.top = y + 'px'; document.body.appendChild(c);
            let angle = Math.random() * Math.PI * 2; let radius = Math.random() * 50 + 50;
            c.animate([ { transform: `translate(0,0) scale(0)`, opacity: 1 }, { transform: `translate(${Math.cos(angle)*radius}px, ${Math.sin(angle)*radius - 50}px) scale(1)`, offset: 0.4 }, { transform: `translate(${uiTarget.left - x}px, ${uiTarget.top - y}px) scale(0.5)`, opacity: 0.5, offset: 1 } ], { duration: 1000 + Math.random()*300, easing: 'ease-in-out' }).onfinish = () => c.remove();
        }
    }

    // --- FÍSICAS DE LA PELOTA ---
    const pelotaEl = document.getElementById('pelota-juego');
    let pelotaInfo = { x: 0, y: -100, vx: 0, vy: 0, isDragging: false, bounceCount: 0, lastX: 0, lastY: 0 };
    let frameId;

    function resetPelota() { pelotaInfo.x = 0; pelotaInfo.y = -100; pelotaInfo.vx = 0; pelotaInfo.vy = 0; pelotaInfo.bounceCount = 0; if(!frameId) physicsLoop(); }

    function physicsLoop() {
        if (habitaciones[habitacionActual].id !== 'juegos') { frameId = null; return; }
        const stageRect = escenario.getBoundingClientRect();
        
        if (!pelotaInfo.isDragging) {
            pelotaInfo.vy += 0.4; pelotaInfo.x += pelotaInfo.vx; pelotaInfo.y += pelotaInfo.vy;
            pelotaInfo.vx *= 0.99; pelotaInfo.vy *= 0.99;

            const radio = 25; const maxX = stageRect.width/2 - radio; const minX = -stageRect.width/2 + radio; const maxY = stageRect.height/2 - radio; const minY = -stageRect.height/2 + radio;
            let reboteActivo = false;
            
            if (pelotaInfo.x > maxX) { pelotaInfo.x = maxX; pelotaInfo.vx *= -0.75; reboteActivo = true; }
            if (pelotaInfo.x < minX) { pelotaInfo.x = minX; pelotaInfo.vx *= -0.75; reboteActivo = true; }
            if (pelotaInfo.y > maxY) { pelotaInfo.y = maxY; pelotaInfo.vy *= -0.75; reboteActivo = true; pelotaInfo.vx *= 0.95; }
            if (pelotaInfo.y < minY) { pelotaInfo.y = minY; pelotaInfo.vy *= -0.75; reboteActivo = true; }

            if (reboteActivo && (Math.abs(pelotaInfo.vx) > 3 || Math.abs(pelotaInfo.vy) > 3)) {
                pelotaInfo.bounceCount++;
                if (pelotaInfo.bounceCount >= 8) { pelotaInfo.bounceCount = 0; darRecompensaPelota(); }
            }
        }
        pelotaEl.style.transform = `translate(${pelotaInfo.x}px, ${pelotaInfo.y}px)`;
        frameId = requestAnimationFrame(physicsLoop);
    }

    function darRecompensaPelota() {
        if (localStats.energia < 5) { mostrarAlerta("¡Sin energía!"); return; }
        const stageRect = escenario.getBoundingClientRect();
        spawnCoinExplosion(stageRect.left + (stageRect.width / 2) + pelotaInfo.x, stageRect.top + (stageRect.height / 2) + pelotaInfo.y);
        ejecutarAccion('recompensa_pelota');
    }

    pelotaEl.addEventListener('mousedown', startDragPelota); pelotaEl.addEventListener('touchstart', startDragPelota, {passive: false});
    function startDragPelota(e) {
        if(localStats.energia < 5) { mostrarAlerta("Sin energía para jugar."); return; }
        e.preventDefault(); pelotaInfo.isDragging = true;
        let clientX = e.type.includes('mouse') ? e.clientX : e.touches[0].clientX; let clientY = e.type.includes('mouse') ? e.clientY : e.touches[0].clientY;
        pelotaInfo.lastX = clientX; pelotaInfo.lastY = clientY;
        document.addEventListener('mousemove', dragPelota); document.addEventListener('touchmove', dragPelota, {passive: false});
        document.addEventListener('mouseup', dropPelota); document.addEventListener('touchend', dropPelota);
    }
    function dragPelota(e) {
        e.preventDefault();
        let clientX = e.type.includes('mouse') ? e.clientX : e.touches[0].clientX; let clientY = e.type.includes('mouse') ? e.clientY : e.touches[0].clientY;
        pelotaInfo.vx = clientX - pelotaInfo.lastX; pelotaInfo.vy = clientY - pelotaInfo.lastY;
        const stageRect = escenario.getBoundingClientRect();
        pelotaInfo.x = clientX - (stageRect.left + stageRect.width/2); pelotaInfo.y = clientY - (stageRect.top + stageRect.height/2);
        pelotaInfo.lastX = clientX; pelotaInfo.lastY = clientY;
    }
    function dropPelota() {
        pelotaInfo.isDragging = false;
        pelotaInfo.vx = Math.min(Math.max(pelotaInfo.vx, -30), 30); pelotaInfo.vy = Math.min(Math.max(pelotaInfo.vy, -30), 30);
        document.removeEventListener('mousemove', dragPelota); document.removeEventListener('touchmove', dragPelota);
        document.removeEventListener('mouseup', dropPelota); document.removeEventListener('touchend', dropPelota);
    }

    // Inicializar
    window.onload = () => {
        fetch(`index.php?get_stats=true`).then(res => res.json()).then(data => actualizarUI(data.stats));
    }
</script>
</body>
</html>