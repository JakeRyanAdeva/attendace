<?php
// Enable error reporting for debugging
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Initialize variables
$formattedData = [];
$inputData = '';

// Check if the form is submitted and handle the request
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get input data from the form
    $inputData = isset($_POST['inputData']) ? trim($_POST['inputData']) : '';

    // Debugging: Show raw input data
    echo "<h2>Raw Input Data:</h2><pre>" . htmlspecialchars($inputData) . "</pre>";

    // Step 1: Parse the Input Data
    $lines = explode("\n", $inputData);
    $data = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        // Debugging: Show individual line
        echo "<h3>Processing Line:</h3><pre>" . htmlspecialchars($line) . "</pre>";
        
        $parts = preg_split('/\s+/', $line);
        if (count($parts) < 4) {
            // Debugging: Show message for invalid line
            echo "<p style='color: red;'>Invalid line (skipped): $line</p>";
            continue; // Skip invalid lines
        }

        list($id, $date, $time, $ampm) = $parts;
        $date = date('j', strtotime($date)); // Day of the month only (1-31)
        $time = $time . ' ' . $ampm;
        $data[$date][] = $time;
    }

    // Debugging: Show parsed data
    echo "<h2>Parsed Data:</h2><pre>";
    print_r($data);
    echo "</pre>";

    // Step 2: Group and Format the Data
    foreach ($data as $day => $times) {
        $morningIn = null;
        $morningOut = null;
        $afternoonIn = null;
        $afternoonOut = null;

        $lastMorningOut = null; // Keeps track of the last valid Morning Out time

        foreach ($times as $time) {
            $timestamp = strtotime($time);
            if ($timestamp === false) {
                // Debugging: Show message for invalid timestamp
                echo "<p style='color: red;'>Invalid time (skipped): $time</p>";
                continue;
            }

            $hour = date('H', $timestamp);

            if ($hour < 12) {
                // Morning In
                if ($morningIn === null) {
                    $morningIn = $timestamp;
                }
            } elseif ($hour >= 12 && $hour < 13) {
                // Time between 12:00 PM and 1:00 PM
                if ($morningOut === null) {
                    $morningOut = $timestamp;
                    $lastMorningOut = $timestamp;
                } else {
                    if ($lastMorningOut !== null && $timestamp > $lastMorningOut + 180 && $afternoonIn === null) {
                        $afternoonIn = $timestamp;
                    }
                }
            } elseif ($hour >= 14 && $afternoonOut === null) {
                // Time after 2:00 PM
                $afternoonOut = $timestamp;
            }
        }

        $formattedData[$day] = [
            'morning_in' => $morningIn ? date('h:i A', $morningIn) : '',
            'morning_out' => $morningOut ? date('h:i A', $morningOut) : '',
            'afternoon_in' => $afternoonIn ? date('h:i A', $afternoonIn) : '',
            'afternoon_out' => $afternoonOut ? date('h:i A', $afternoonOut) : ''
        ];
    }

    // Debugging: Show formatted data
    echo "<h2>Formatted Data:</h2><pre>";
    print_r($formattedData);
    echo "</pre>";
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Time Data Results</title>
</head>
<body>
    <?php if (!empty($formattedData)): ?>
        <table border="1" style="margin-top: 20px;">
            <tr>
                <th>Date</th>
                <th>Morning In</th>
                <th>Morning Out</th>
                <th>Afternoon In</th>
                <th>Afternoon Out</th>
            </tr>
            <?php for ($i = 1; $i <= 31; $i++): ?>
            <tr>
                <td><?php echo $i; ?></td>
                <td><?php echo isset($formattedData[$i]) ? $formattedData[$i]['morning_in'] : ''; ?></td>
                <td><?php echo isset($formattedData[$i]) ? $formattedData[$i]['morning_out'] : ''; ?></td>
                <td><?php echo isset($formattedData[$i]) ? $formattedData[$i]['afternoon_in'] : ''; ?></td>
                <td><?php echo isset($formattedData[$i]) ? $formattedData[$i]['afternoon_out'] : ''; ?></td>
            </tr>
            <?php endfor; ?>
        </table>
    <?php endif; ?>
</body>
</html>
