<?php
include 'db.php';

// Fetch historical data for the top 10 alliances
$result = $conn->query("
    SELECT aph.alliance_id, a.full_name, aph.power, aph.date_recorded
    FROM alliance_power_history aph
    JOIN alliances a ON aph.alliance_id = a.id
    WHERE a.ranking <= 10
    ORDER BY aph.date_recorded ASC
");

$data = [];
$dates = [];
while ($row = $result->fetch_assoc()) {
    $data[$row['full_name']][] = [
        'date' => $row['date_recorded'],
        'power' => $row['power']
    ];
    if (!in_array($row['date_recorded'], $dates)) {
        $dates[] = $row['date_recorded'];
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Alliance Power History</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/moment@2.29.1/min/moment.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-adapter-moment"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-zoom@1.2.1/dist/chartjs-plugin-zoom.min.js"></script> <!-- Add this line -->
</head>
<body>

<canvas id="historyChart"></canvas>

<script>
    const ctx = document.getElementById('historyChart').getContext('2d');
    const chartData = {
        labels: [<?php echo implode(',', array_map(function($d) { return "'" . $d . "'"; }, $dates)); ?>],
        datasets: [
            <?php foreach ($data as $name => $values): ?>
            {
                label: '<?php echo $name; ?>',
                data: [<?php echo implode(',', array_map(function($v) { return "{x: '" . $v['date'] . "', y: " . $v['power'] . "}"; }, $values)); ?>],
                fill: false,
                borderColor: '<?php echo sprintf('#%06X', mt_rand(0, 0xFFFFFF)); ?>',
                tension: 0.1,
                pointRadius: 3,
                pointHoverRadius: 5,
            },
            <?php endforeach; ?>
        ]
    };

    new Chart(ctx, {
        type: 'line',
        data: chartData,
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'Alliance Power Development Over Time'
                },
                zoom: {
                    pan: {
                        enabled: true,
                        mode: 'xy', // Allow panning in both x and y directions
                    },
                    zoom: {
                        wheel: {
                            enabled: true, // Enable zooming with the mouse wheel
                        },
                        pinch: {
                            enabled: true // Enable zooming by pinching on touch devices
                        },
                        mode: 'xy' // Allow zooming in both x and y directions
                    }
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            let label = context.dataset.label || '';
                            if (label) {
                                label += ': ';
                            }
                            label += context.parsed.y.toLocaleString();
                            return label;
                        }
                    }
                }
            },
            scales: {
                x: {
                    type: 'time',
                    time: {
                        unit: 'day',
                        tooltipFormat: 'YYYY-MM-DD HH:mm:ss'
                    },
                    title: {
                        display: true,
                        text: 'Date'
                    }
                },
                y: {
                    beginAtZero: true,
                    ticks: {
                        callback: function(value) {
                            if (value >= 1000000000) {
                                return (value / 1000000000).toFixed(2) + 'B';
                            } else if (value >= 1000000) {
                                return (value / 1000000).toFixed(2) + 'M';
                            }
                            return value;
                        }
                    },
                    title: {
                        display: true,
                        text: 'Power'
                    }
                }
            }
        }
    });
</script>

</body>
</html>
