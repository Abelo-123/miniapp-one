<?php
// admin/api_ai_assistant.php - The Ultimate Paxyo AI Assistant (Intelligence + Data)
session_start();
include '../db.php';

if (!isset($_SESSION['admin_authenticated']) || $_SESSION['admin_authenticated'] !== true) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

header('Content-Type: application/json');

$action = $_POST['action'] ?? '';

// --- HELPER: GET SERVICE NAME ---
function get_service_info($id) {
    $cacheFile = '../cache/services.json';
    if (!file_exists($cacheFile)) return ['name' => "Service #$id", 'rate' => 0, 'min' => 0, 'max' => 0];
    $services = json_decode(file_get_contents($cacheFile), true);
    foreach ($services as $s) {
        if (($s['service'] ?? 0) == $id) return $s;
    }
    return ['name' => "Service #$id", 'rate' => 0, 'min' => 0, 'max' => 0];
}

if ($action === 'chat') {
    $message = $_POST['message'] ?? '';
    if (empty($message)) {
        echo json_encode(['success' => false, 'error' => 'Empty message']);
        exit;
    }

    // --- 1. CONVERSATION MEMORY ---
    $last_topic = $_SESSION['ai_last_topic'] ?? 'general';
    $last_data = $_SESSION['ai_last_data'] ?? [];

    $today = date('Y-m-d');
    $yesterday = date('Y-m-d', strtotime('-1 day'));
    $seven_days_ago = date('Y-m-d', strtotime('-7 days'));

    // --- 2. GATHER MASTER CONTEXT ---
    
    // Global Totals
    $u_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM auth"))['c'];
    $o_count = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c FROM orders"))['c'];
    $rev_total = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(charge) as c FROM orders WHERE status != 'cancelled'"))['c'] ?? 0;
    
    // Growth & Prediction Data
    $rev_7d = mysqli_fetch_assoc(mysqli_query($conn, "SELECT SUM(charge) as c FROM orders WHERE DATE(created_at) >= '$seven_days_ago' AND status != 'cancelled'"))['c'] ?? 0;
    $avg_daily_rev = $rev_7d / 7;
    $predicted_30d = $avg_daily_rev * 30;

    // Anomalies
    $anomalies = [];
    $user_fails = mysqli_query($conn, "SELECT user_id, COUNT(*) as c FROM deposits WHERE status != 'completed' AND created_at >= '$today' GROUP BY user_id HAVING c >= 3");
    while($row = mysqli_fetch_assoc($user_fails)) $anomalies[] = "User **" . $row['user_id'] . "** failed **" . $row['c'] . "** deposits today.";
    
    $big_orders = mysqli_query($conn, "SELECT id, charge FROM orders WHERE charge > 500 AND created_at >= '$today'");
    while($row = mysqli_fetch_assoc($big_orders)) $anomalies[] = "Large order **#".$row['id']."** detected today (".number_format($row['charge'], 2)." ETB).";

    $msg = strtolower(trim($message));
    $response = "";
    $new_topic = $last_topic;
    $new_data = $last_data;

    // --- 3. ANALYZE INTENTS ---
    $is_searching = preg_match('/\d+/', $msg, $id_matches);
    $target_id = $id_matches[0] ?? null;

    $intents = [
        'prediction' => preg_match('/predict|future|forecast|projection/i', $msg),
        'anomaly' => preg_match('/suspicious|anomaly|danger|error|wrong|weird|security/i', $msg),
        'history' => preg_match('/why|reason|log|history|what happened/i', $msg),
        'user' => preg_match('/user|member|people|customer/i', $msg),
        'money' => preg_match('/money|revenue|cash|earn|deposit|balance/i', $msg),
        'service' => preg_match('/service|offer|product/i', $msg),
        'order' => preg_match('/order|purchase|buy/i', $msg),
        'joke' => preg_match('/joke|laugh|funny/i', $msg),
        'meta' => preg_match('/who are you|capabilities|how smart|what can you/i', $msg),
        'newness' => preg_match('/new|recent|latest|any/i', $msg)
    ];

    // --- 4. DATA-DRIVEN RESPONSES ---

    // A. DEEP SEARCH (ID Context)
    if ($target_id && !$intents['prediction'] && !$intents['anomaly']) {
        // Search Orders
        $order = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders WHERE id = '$target_id' OR api_order_id = '$target_id'"));
        if ($order) {
            $s = get_service_info($order['service_id']);
            $resp = "Found Order **#".$order['id']."** for **".$s['name']."**. \n";
            $resp .= "Status: **".strtoupper($order['status'])."** | Price: **".number_format($order['charge'], 2)." ETB**. \n";
            
            $log = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM order_logs WHERE order_id = '".$order['id']."' ORDER BY id DESC LIMIT 1"));
            if ($log) $resp .= "📝 **Last Event**: ".$log['action']." (".$log['old_status']." ➡️ ".$log['new_status'].") on ".date('M d, H:i', strtotime($log['created_at']));
            
            $response = $resp;
            $new_topic = 'order'; $new_data = ['last_order_id' => $order['id']];
        } 
        // Search Users
        else {
            $user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM auth WHERE tg_id = '$target_id' OR id = '$target_id' OR tg_username LIKE '%$target_id%'"));
            if ($user) {
                $u_stats = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as c, SUM(charge) as s FROM orders WHERE user_id = '".$user['tg_id']."'"));
                $response = "User **@".$user['tg_username']."** (ID: ".$user['tg_id'].") has **".number_format($user['balance'], 2)." ETB**. \n";
                $response .= "History: **".$u_stats['c']." orders** totaling **".number_format($u_stats['s'] ?? 0, 2)." ETB**. 🏆";
                $new_topic = 'user'; $new_data = ['top_user_id' => $user['tg_id'], 'top_username' => $user['tg_username']];
            }
        }
    }

    // B. SPECIALIZED INTELLIGENCE
    if (empty($response)) {
        if ($intents['prediction']) {
            $response = "🔭 **Forecast**: Based on our 7-day average of **".number_format($avg_daily_rev, 2)." ETB/day**, we are projected to reach **".number_format($predicted_30d, 2)." ETB** this month. Business is " . ($avg_daily_rev > 0 ? "healthy" : "waiting for a spark") . "! 📈";
            $new_topic = 'money';
        } 
        elseif ($intents['anomaly']) {
            $response = empty($anomalies) ? "Everything looks solid! No suspicious patterns detected in the last 24 hours. ✅" : "🚨 **Anomalies Detected**: \n- " . implode("\n- ", $anomalies);
            $new_topic = 'security';
        }
        elseif ($intents['history'] && $last_topic === 'order' && !empty($last_data['last_order_id'])) {
            $oid = $last_data['last_order_id'];
            $logs = mysqli_query($conn, "SELECT * FROM order_logs WHERE order_id = '$oid' ORDER BY id ASC");
            $resp = "Timeline for Order **#$oid**: \n";
            while($l = mysqli_fetch_assoc($logs)) $resp .= "- ".date('M d, H:i', strtotime($l['created_at'])).": ".$l['action']." (".$l['old_status']." ➡️ ".$l['new_status'].") \n";
            $response = $resp ?: "No historical logs found for this order.";
        }
        elseif ($intents['order']) {
            if ($intents['newness']) {
                $last_o = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM orders ORDER BY id DESC LIMIT 1"));
                $s = get_service_info($last_o['service_id']);
                $response = "The most recent order is **#".$last_o['id']."** (".$s['name'].") placed just now at **".date('H:i', strtotime($last_o['created_at']))."**. It's currently **".$last_o['status']."**. \n\nWe have **".$stats['pending']." orders** total waiting for processing! 🚀";
            } else {
                $response = "Platform total: **".$stats['orders']." orders**. right now, **".$stats['pending']."** are pending. We're keeping things busy! 🚀";
            }
            $new_topic = 'orders';
        }
        elseif ($intents['user']) {
            $response = "We have **$u_count registered users**. Our top customer is active today! Ask me for a 'forecast' to see how they impact our revenue. 👥";
        }
        elseif ($intents['money']) {
            $response = "Total revenue: **".number_format($rev_total, 2)." ETB**. Today's performance: **".number_format($rev_7d/7, 2)." ETB** average. 💰";
        }
        elseif ($intents['service']) {
            if ($target_id) { $s = get_service_info($target_id); $response = "Service **#$target_id**: **".$s['name']."**. Rate: ".$s['rate']." ETB. ⚙️"; }
            else { $response = "We have a full catalog of services. Ask me for a specific ID!"; }
        }
        elseif ($intents['joke']) {
            $j = ["Why did the admin go broke? Because he lost his 'query' for success! 😂", "SQL walks into a bar: 'Select beer from taps where cold = 1' 🍺"];
            $response = $j[array_rand($j)];
        }
        elseif ($intents['meta']) {
            $response = "I am **Paxyo Hybrid Intelligence**. I combine real-time SQL data with predictive forecasting to help you manage this platform. 🦾";
        }
    }

    // C. CATCH-ALL
    if (empty($response)) {
        $response = "I'm not quite sure how to handle that yet, boss. Try asking about **'new orders'**, **'revenue forecast'**, or **'search user 111'**. I'm here to help! 🕵️";
    }

    // Save context
    $_SESSION['ai_last_topic'] = $new_topic;
    $_SESSION['ai_last_data'] = $new_data;

    echo json_encode(['success' => true, 'response' => $response, 'topic' => $new_topic, 'timestamp' => time()]);
    exit;
}

echo json_encode(['success' => false, 'error' => 'Invalid action']);
