<?php
    $sql_institute = "SELECT institute, COUNT(institute) AS institute_count 
            FROM mlsi_nipt.result_info 
            GROUP BY institute 
            ORDER BY institute_count DESC 
            LIMIT 12";

    $stmt = $pdo->query($sql_institute);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $institutes = [];
    $counts_institute = [];

    foreach ($rows as $row) {
        // Fallback to 'Unknown' if the institute field happens to be blank/null
        $institutes[] = $row['institute'] ?? 'Unknown';
        $counts_institute[] = (int)($row['institute_count'] ?? 0);
    }
?>


<section class="container mt-3">
    <div class="row">
        <div class="col-md-8 mx-auto">
            <canvas id="instituteChart"></canvas>
        </div>
        <div class="col-md-4">
            <p><b>Table 4. </b>Top 12 Client Hospitals DMSc-NIPT Sender</p>
            <table class="table table-sm table-hover" style="--bs-table-bg: transparent;">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="fw-light text-center">Rank</th>
                        <th scope="col" class="fw-light">Name</th>
                        <th scope="col" class="fw-light text-center"><i>N</i></th>
                        <th scope="col" class="fw-light text-center">%</th>
                    </tr>
                </thead>
                <tbody>
                <?php 
                    $rank = 1; 
                    foreach ($rows as $row): 
                        $institute_name = $row['institute'];
                        $count = (int)$row['institute_count'];
                    ?>
                    <tr>
                        <td class="text-secondary text-center"><strong><?php echo $rank++; ?></strong></td>
                        <td class="text-secondary"><?php echo $institute_name; ?></td>
                        <td class="text-secondary text-center"><?php echo number_format($count); ?></td>
                        <td class="text-secondary text-center"><?php echo round((number_format($count) / $maternal_count)*100, 2); ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
</section>


<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        const labelsData = <?php echo json_encode($institutes); ?>;
        const valuesData = <?php echo json_encode($counts_institute); ?>;

        const ctx = document.getElementById('instituteChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar', // Change this to 'indexAxis: "y"' inside options if you prefer horizontal bars!
            data: {
                labels: labelsData,
                datasets: [{
                    label: 'Sample Count',
                    data: valuesData,
                    backgroundColor: 'rgba(255, 162, 235, 0.6)',
                    borderColor: 'rgba(255, 162, 235, 1)',
                    borderWidth: 1
                }]
            },
            options: {
                indexAxis: 'y',
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'DMSc-NIPT Top 12 Client Hospitals by Sample Count',
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
                            text: 'Number of Samples',
                        },
                        ticks: {
                            // Automatically rotates labels if they are too long to prevent overlapping
                            maxRotation: 45,
                            minRotation: 45
                        }
                    },
                    y: {
                        beginAtZero: false,
                    }
                }
            }
        });
    });
</script>