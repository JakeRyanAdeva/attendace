<?php
// Initialize variables
$formattedData = [];
$inputData = '';

// Check if the form is submitted and handle the request
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Get input data from the form
    $inputData = isset($_POST['inputData']) ? trim($_POST['inputData']) : '';

    // Step 1: Parse the Input Data
    $lines = explode("\n", $inputData);
    $data = [];

    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line)) continue;
        
        $parts = preg_split('/\s+/', $line);
        if (count($parts) < 4) {
            continue; // Skip invalid lines
        }

        list($id, $date, $time, $ampm) = $parts;
        $date = date('j', strtotime($date)); // Day of the month only (1-31)
        $time = $time . ' ' . $ampm;
        $data[$date][] = $time;
    }

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
}
?>
<!DOCTYPE html>
<html>
<head>
    <title>Time Data Input and Results</title>
</head>
<body>
    <form action="" method="post">
        <textarea name="inputData" rows="20" cols="80" placeholder="Enter your data here..."><?php echo isset($inputData) ? htmlspecialchars($inputData) : ''; ?></textarea><br>
        <input type="submit" value="Submit Data">
    </form>

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
