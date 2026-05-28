<?php
    $sql_fail = "SELECT 
                RUN,
                COUNT(*) AS total_samples,
                SUM(CASE WHEN Sample_QC = 'Fail' THEN 1 ELSE 0 END) AS fail_count,
                ROUND(
                    (SUM(CASE WHEN Sample_QC = 'Fail' THEN 1 ELSE 0 END) / COUNT(*)) * 100,
                    2
                ) AS fail_percent
            FROM mlsi_nipt.result_halos
            GROUP BY RUN
            ORDER BY RUN ASC"; // Ordered chronologically/sequentially for a smooth line flow

    $stmt = $pdo->query($sql_fail);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $run_labels = [];
    $fail_counts = [];

    foreach ($rows as $row) {
        $run_labels[] = $row['RUN'];
        $fail_counts[] = (int)($row['fail_count'] ?? 0);
    }
?>
    
    
<section class="container mt-3">
    <div class="row">
        <div class="col-md-9 mx-auto">
            <canvas id="runFailureChart"></canvas>
        </div>
        <div class="col-md-3">
            <p><b>Table 1. </b>Fail Rate per RUN</p>
            <table class="table table-hover table-sm display compact small" style="--bs-table-bg: transparent;">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="fw-light text-center">RUN</th>
                        <th scope="col" class="fw-light text-center">Samples</th>
                        <th scope="col" class="fw-light text-center">Fails</th>
                        <th scope="col" class="fw-light text-center">%</th>
                    </tr>
                </thead>
                <tbody>
                <?php
                    $limit = 15;
                    $counter = 0;
                    foreach (array_reverse($rows) as $row): 
                        if ($counter >= $limit) {
                            break;
                        }
                        $run = $row['RUN'];
                        $total = (int)$row['total_samples'];
                        $fail = (int)$row['fail_count'];
                        $percent = (float)$row['fail_percent'];
                        $counter++;
                ?>
                <tr>
                    <td class="text-secondary text-center"><?php echo $run; ?></td>
                    <td class="text-secondary text-center"><?php echo number_format($total); ?></td>
                    <td class="text-secondary text-center"><?php echo number_format($fail); ?></td>
                    <td class="text-secondary text-center text-danger"><?php echo number_format($percent, 2); ?> %</td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>


<script>
    var table2 = $('#myTable2').DataTable({
                    'paging': true,
                    pageLength: 100
                });

    // var length = table2.rows().count();;
    // document.getElementById("rowcount").innerHTML = '(' + length + ' Items)';
</script>


<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        const chartLabels = <?php echo json_encode($run_labels); ?>;
        const chartDataValues = <?php echo json_encode($fail_counts); ?>;

        const ctx = document.getElementById('runFailureChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'line',
            data: {
                labels: chartLabels,
                datasets: [{
                    label: 'Fail Count',
                    data: chartDataValues,
                    backgroundColor: 'rgba(255, 99, 132, 0.2)',
                    borderColor: 'rgba(255, 99, 132, 1)',
                    borderWidth: 2,
                    tension: 0.2,
                    pointBackgroundColor: 'rgba(255, 99, 132, 1)',
                    pointRadius: 3,
                    pointHoverRadius: 5,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'DMSc-NIPT Sequencing Run Quality Tracking (Sample Failures)',
                        font: { size: 14, weight: 'bold' }
                    },
                    legend: {
                        display: false
                    }
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'RUN Identifier'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        max: 40,
                        title: {
                            display: true,
                            text: 'Number of Failed Samples'
                        },
                        ticks: {
                            stepSize: 1 // Forces clean whole integer intervals on case volumes
                        }
                    }
                }
            }
        });
    });
</script>