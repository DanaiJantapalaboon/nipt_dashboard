<?php
    // --- DATASET 1: Male ---
    $sql_male = "SELECT 
                    COUNT(CASE WHEN `Test(chr13)` LIKE '%13' THEN 1 END) AS chr13_count,
                    COUNT(CASE WHEN `Test(chr18)` LIKE '%18' THEN 1 END) AS chr18_count,
                    COUNT(CASE WHEN `Test(chr21)` LIKE '%21' THEN 1 END) AS chr21_count,
                    COUNT(CASE WHEN `Test(Sex_chr)` LIKE 'XO%' THEN 1 END) AS xo_count,
                    COUNT(CASE WHEN `Test(Sex_chr)` LIKE 'XXX%' THEN 1 END) AS xxx_count,
                    COUNT(CASE WHEN `Test(Sex_chr)` LIKE 'XXY%' THEN 1 END) AS xxy_count,
                    COUNT(CASE WHEN `Test(Sex_chr)` LIKE 'XYY%' THEN 1 END) AS xyy_count
                FROM result_halos
                WHERE OperationAdvice = 'Qualified'
                AND Gender = 'Male'
                AND Comment NOT IN ('Relibrary', 'Negative')
                AND Patient_Name NOT IN ('Positive Control', 'Negative Control')";

    $stmt_male = $pdo->query($sql_male);
    $row_male = $stmt_male->fetch(PDO::FETCH_ASSOC);

    // --- DATASET 2: Female ---
    $sql_female = "SELECT 
                    COUNT(CASE WHEN `Test(chr13)` LIKE '%13' THEN 1 END) AS chr13_count,
                    COUNT(CASE WHEN `Test(chr18)` LIKE '%18' THEN 1 END) AS chr18_count,
                    COUNT(CASE WHEN `Test(chr21)` LIKE '%21' THEN 1 END) AS chr21_count,
                    COUNT(CASE WHEN `Test(Sex_chr)` LIKE 'XO%' THEN 1 END) AS xo_count,
                    COUNT(CASE WHEN `Test(Sex_chr)` LIKE 'XXX%' THEN 1 END) AS xxx_count,
                    COUNT(CASE WHEN `Test(Sex_chr)` LIKE 'XXY%' THEN 1 END) AS xxy_count,
                    COUNT(CASE WHEN `Test(Sex_chr)` LIKE 'XYY%' THEN 1 END) AS xyy_count
                FROM result_halos
                WHERE OperationAdvice = 'Qualified'
                AND Gender = 'Female'
                AND Comment NOT IN ('Relibrary', 'Negative')
                AND Patient_Name NOT IN ('Positive Control', 'Negative Control')";

    $stmt_female = $pdo->query($sql_female);
    $row_female = $stmt_female->fetch(PDO::FETCH_ASSOC);

    $male_data = [
        (int)($row_male['chr13_count'] ?? 0),
        (int)($row_male['chr18_count'] ?? 0),
        (int)($row_male['chr21_count'] ?? 0),
        (int)($row_male['xo_count'] ?? 0),
        (int)($row_male['xxx_count'] ?? 0),
        (int)($row_male['xxy_count'] ?? 0),
        (int)($row_male['xyy_count'] ?? 0)
    ];

    $female_data = [
        (int)($row_female['chr13_count'] ?? 0),
        (int)($row_female['chr18_count'] ?? 0),
        (int)($row_female['chr21_count'] ?? 0),
        (int)($row_female['xo_count'] ?? 0),
        (int)($row_female['xxx_count'] ?? 0),
        (int)($row_female['xxy_count'] ?? 0),
        (int)($row_female['xyy_count'] ?? 0)
    ];
?>

<section class="container mt-3">
    <div class="row">
        <div class="col-md-6">
            <canvas id="chromosomeChart"></canvas>
        </div>
        <div class="col-md-6">
            <p><b>Table 1. </b>Chromosome Abnormaly Incident Rate</p>
            <table class="table table-sm table-hover" style="--bs-table-bg: transparent;">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="fw-light">Chromosome Tests</th>
                        <th scope="col" class="fw-light text-center">Male</th>
                        <th scope="col" class="fw-light text-center">%</th>
                        <th scope="col" class="fw-light text-center">Female</th>
                        <th scope="col" class="fw-light text-center">%</th>
                        <th scope="col" class="fw-light text-center"><i>N</i></th>
                        <th scope="col" class="fw-light text-center">Ratio</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="text-secondary">Trisomy 13 (Patau Syndrome)</td>
                        <td class="text-primary text-center"><?php echo $row_male['chr13_count']; ?></td>
                        <td class="text-primary text-center"><?php echo round(($row_male['chr13_count'] / $maternal_count) * 100, 4); ?></td>
                        <td class="text-danger text-center"><?php echo $row_female['chr13_count']; ?></td>
                        <td class="text-danger text-center"><?php echo round(($row_female['chr13_count'] / $maternal_count) * 100, 4); ?></td>
                        <td class="text-secondary text-center"><?php echo $row_female['chr13_count'] + $row_male['chr13_count']; ?></td>
                        <td class="text-secondary text-center">1 : <?php echo round($maternal_count / ($row_female['chr13_count'] + $row_male['chr13_count']), 0); ?></td>
                    </tr>
                    <tr>
                        <td class="text-secondary">Trisomy 18 (Edwards Syndrome)</td>
                        <td class="text-primary text-center"><?php echo $row_male['chr18_count']; ?></td>
                        <td class="text-primary text-center"><?php echo round(($row_male['chr18_count'] / $maternal_count) * 100, 4); ?></td>
                        <td class="text-danger text-center"><?php echo $row_female['chr18_count']; ?></td>
                        <td class="text-danger text-center"><?php echo round(($row_female['chr18_count'] / $maternal_count) * 100, 4); ?></td>
                        <td class="text-secondary text-center"><?php echo $row_female['chr18_count'] + $row_male['chr18_count']; ?></td>
                        <td class="text-secondary text-center">1 : <?php echo round($maternal_count / ($row_female['chr18_count'] + $row_male['chr18_count']), 0); ?></td>
                    </tr>
                    <tr>
                        <td class="text-secondary">Trisomy 21 (Down Syndrome)</td>
                        <td class="text-primary text-center"><?php echo $row_male['chr21_count']; ?></td>
                        <td class="text-primary text-center"><?php echo round(($row_male['chr21_count'] / $maternal_count) * 100, 4); ?></td>
                        <td class="text-danger text-center"><?php echo $row_female['chr21_count']; ?></td>
                        <td class="text-danger text-center"><?php echo round(($row_female['chr21_count'] / $maternal_count) * 100, 4); ?></td>
                        <td class="text-secondary text-center"><?php echo $row_female['chr21_count'] + $row_male['chr21_count']; ?></td>
                        <td class="text-secondary text-center">1 : <?php echo round($maternal_count / ($row_female['chr21_count'] + $row_male['chr21_count']), 0); ?></td>
                    </tr>
                    <tr>
                        <td class="text-secondary">XO (Turner Syndrome)</td>
                        <td class="text-primary text-center">-</td>
                        <td class="text-primary text-center">-</td>
                        <td class="text-danger text-center"><?php echo $row_female['xo_count']; ?></td>
                        <td class="text-danger text-center"><?php echo round(($row_female['xo_count'] / $maternal_count) * 100, 4); ?></td>
                        <td class="text-secondary text-center"><?php echo $row_female['xo_count']; ?></td>
                        <td class="text-secondary text-center">1 : <?php echo round($maternal_count / ($row_female['xo_count'] + $row_male['xo_count']), 0); ?></td>
                    </tr>
                    <tr>
                        <td class="text-secondary">XXX (Triple X Syndrome)</td>
                        <td class="text-primary text-center">-</td>
                        <td class="text-primary text-center">-</td>
                        <td class="text-danger text-center"><?php echo $row_female['xxx_count']; ?></td>
                        <td class="text-danger text-center"><?php echo round(($row_female['xxx_count'] / $maternal_count) * 100, 4); ?></td>
                        <td class="text-secondary text-center"><?php echo $row_female['xxx_count']; ?></td>
                        <td class="text-secondary text-center">1 : <?php echo round($maternal_count / ($row_female['xxx_count'] + $row_male['xxx_count']), 0); ?></td>
                    </tr>
                    <tr>
                        <td class="text-secondary">XXY (Klinefelter Syndrome)</td>
                        <td class="text-primary text-center"><?php echo $row_male['xxy_count']; ?></td>
                        <td class="text-primary text-center"><?php echo round(($row_male['xxy_count'] / $maternal_count) * 100, 4); ?></td>
                        <td class="text-danger text-center">-</td>
                        <td class="text-danger text-center">-</td>
                        <td class="text-secondary text-center"><?php echo $row_male['xxy_count']; ?></td>
                        <td class="text-secondary text-center">1 : <?php echo round($maternal_count / ($row_female['xxy_count'] + $row_male['xxy_count']), 0); ?></td>
                    </tr>
                    <tr>
                        <td class="text-secondary">XYY (Jacobs syndrome)</td>
                        <td class="text-primary text-center"><?php echo $row_male['xyy_count']; ?></td>
                        <td class="text-primary text-center"><?php echo round(($row_male['xyy_count'] / $maternal_count) * 100, 4); ?></td>
                        <td class="text-danger text-center">-</td>
                        <td class="text-danger text-center">-</td>
                        <td class="text-secondary text-center"><?php echo $row_male['xyy_count']; ?></td>
                        <td class="text-secondary text-center">1 : <?php echo round($maternal_count / ($row_female['xyy_count'] + $row_male['xyy_count']), 0); ?></td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>


<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        const maleDataset = <?php echo json_encode($male_data); ?>;
        const femaleDataset = <?php echo json_encode($female_data); ?>;

        const ctx = document.getElementById('chromosomeChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: ['Chr 13', 'Chr 18', 'Chr 21', 'XO', 'XXX', 'XXY', 'XYY'],
                datasets: [
                    {
                        label: 'Male',
                        data: maleDataset,
                        backgroundColor: 'rgba(54, 162, 235, 0.7)',
                        borderColor: 'rgba(54, 162, 235, 1)',
                        borderWidth: 1
                    },
                    {
                        label: 'Female',
                        data: femaleDataset,
                        backgroundColor: 'rgba(255, 99, 132, 0.7)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }
                ]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    },
                    title: {
                        display: true,
                        text: 'DMSc-NIPT High Risk Screening Grouped by Fetal Gender',
                        font: { size: 14, weight: 'bold' }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        min: 0,
                        max: 100,
                        title: {
                            display: true,
                            text: 'Case Counts'
                        }
                    }
                }
            }
        });
    });
</script>