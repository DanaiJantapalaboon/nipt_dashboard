<?php
    // DOB Year
    $sql = "SELECT (2569 - DOB_Year) AS AGE, COUNT(*) AS total_count FROM result_info 
            WHERE DOB_Year IS NOT NULL
            GROUP BY AGE 
            ORDER BY AGE ASC";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ages = [];
    $counts = [];

    foreach ($rows as $row) {
        $ages[] = (int)$row['AGE'];
        $counts[] = (int)$row['total_count'];
    }

    // Age Distribution Table
    $sql_agedist = "WITH grouped_data AS (
        SELECT 
            (2569 - DOB_Year) AS age,
            CASE
                WHEN (2569 - DOB_Year) BETWEEN 10 AND 19 THEN 'Teenage (10-19 Years)'
                WHEN (2569 - DOB_Year) BETWEEN 20 AND 35 THEN 'Biological Peak (20-35 Years)'
                WHEN (2569 - DOB_Year) BETWEEN 36 AND 44 THEN 'Advanced Maternal Age (AMA) (36-44 Years)'
                WHEN (2569 - DOB_Year) >= 45 THEN 'Very Advanced Maternal Age (VAMA) (45+ Years)'
            END AS age_group
        FROM result_info
        WHERE DOB_Year IS NOT NULL
    ),

    ranked_data AS (
        SELECT 
            age,
            age_group,
            ROW_NUMBER() OVER (PARTITION BY age_group ORDER BY age) AS rn,
            COUNT(*) OVER (PARTITION BY age_group) AS total_rows
        FROM grouped_data
    ),

    group_stats AS (
        SELECT 
            age_group,
            COUNT(*) AS total_count,

            ROUND(
                (COUNT(*) * 100.0) / (SELECT COUNT(*) FROM grouped_data),
            2) AS percent_total,

            ROUND(AVG(age), 2) AS mean_age,

            ROUND(
                AVG(
                    CASE 
                        WHEN rn IN (
                            FLOOR((total_rows + 1) / 2),
                            FLOOR((total_rows + 2) / 2)
                        )
                        THEN age
                    END
                ),
            2) AS median_age

        FROM ranked_data
        GROUP BY age_group
    )

    SELECT 
        gs.age_group,
        gs.total_count,
        CONCAT(gs.percent_total, '%') AS percent_total,
        gs.mean_age,
        gs.median_age,

        (
            SELECT age
            FROM grouped_data gd2
            WHERE gd2.age_group = gs.age_group
            GROUP BY age
            ORDER BY COUNT(*) DESC, age
            LIMIT 1
        ) AS mode_age

    FROM group_stats gs

    UNION ALL

    SELECT
        'Total' AS age_group,
        COUNT(*) AS total_count,
        '100%' AS percent_total,

        ROUND(AVG(age), 2) AS mean_age,

        ROUND(
            AVG(
                CASE 
                    WHEN rn IN (
                        FLOOR((total_rows + 1) / 2),
                        FLOOR((total_rows + 2) / 2)
                    )
                    THEN age
                END
            ),
        2) AS median_age,

        (
            SELECT age
            FROM grouped_data
            GROUP BY age
            ORDER BY COUNT(*) DESC, age
            LIMIT 1
        ) AS mode_age

    FROM (
        SELECT 
            age,
            ROW_NUMBER() OVER (ORDER BY age) AS rn,
            COUNT(*) OVER () AS total_rows
        FROM grouped_data
    ) total_calc

    ORDER BY 
        CASE age_group
            WHEN 'Teenage (10-19 Years)' THEN 1
            WHEN 'Biological Peak (20-35 Years)' THEN 2
            WHEN 'Advanced Maternal Age (AMA) (36-44 Years)' THEN 3
            WHEN 'Very Advanced Maternal Age (VAMA) (45+ Years)' THEN 4
            WHEN 'Total' THEN 5
        END";

    $stmt_agedist = $pdo->query($sql_agedist);
    $row_age = $stmt_agedist->fetch(PDO::FETCH_ASSOC);
?>


<section class="container mt-3">
    <div class="row">
        <div class="col-md-6">
            <canvas id="ageDistributionChart"></canvas>
        </div>
        <div class="col-md-6">
            <p><b>Table 2. </b>Maternal Age Distribution</p>
            <table class="table table-sm table-hover" style="--bs-table-bg: transparent;">
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="fw-light">Age Groups</th>
                        <th scope="col" class="fw-light text-center"><i>N</i></th>
                        <th scope="col" class="fw-light text-center">%</th>
                        <th scope="col" class="fw-light text-center">Mean</th>
                        <th scope="col" class="fw-light text-center">Median</th>
                        <th scope="col" class="fw-light text-center">Mode</th>
                    </tr>
                </thead>
                <tbody>
                    <td class="text-secondary"><?php echo $row_age['age_group']; ?></td>
                    <td class="text-secondary text-center"><?php echo $row_age['total_count']; ?></td>
                    <td class="text-secondary text-center"><?php echo $row_age['percent_total']; ?></td>
                    <td class="text-secondary text-center"><?php echo $row_age['mean_age']; ?></td>
                    <td class="text-secondary text-center"><?php echo $row_age['median_age']; ?></td>
                    <td class="text-secondary text-center"><?php echo $row_age['mode_age']; ?></td>
                <?php
                    while ($row_age = $stmt_agedist->fetch(PDO::FETCH_ASSOC)) {
                        echo "<tr>";
                        echo "<td class='text-secondary'>{$row_age['age_group']}</td>";
                        echo "<td class='text-secondary text-center'>{$row_age['total_count']}</td>";
                        echo "<td class='text-secondary text-center'>{$row_age['percent_total']}</td>";
                        echo "<td class='text-secondary text-center'>{$row_age['mean_age']}</td>";
                        echo "<td class='text-secondary text-center'>{$row_age['median_age']}</td>";
                        echo "<td class='text-secondary text-center'>{$row_age['mode_age']}</td>";
                        echo "</tr>";
                    }
                ?>
                </tbody>
            </table>
        </div>
    </div>
</section>


<script type="module">
    document.addEventListener("DOMContentLoaded", function() {
        const ageLabels = <?php echo json_encode($ages); ?>;
        const distributionData = <?php echo json_encode($counts); ?>;

        const ctx = document.getElementById('ageDistributionChart');
        if (!ctx) return;

        new Chart(ctx, {
            type: 'bar', 
            data: {
                labels: ageLabels,
                datasets: [{
                    label: 'Maternal Counts',
                    data: distributionData,
                    backgroundColor: 'rgba(75, 192, 192, 0.6)',
                    borderColor: 'rgba(75, 192, 192, 1)',
                    borderWidth: 1,
                    barPercentage: 0.9,  // Slightly widens bars to resemble a classic histogram
                    categoryPercentage: 1.0
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    title: {
                        display: true,
                        text: 'DMSc-NIPT Maternal Age Distribution',
                        font: { size: 14, weight: 'bold' }
                    },
                    legend: { display: false } // Hide legend since it's a single tracking dataset
                },
                scales: {
                    x: {
                        title: {
                            display: true,
                            text: 'Age (Years Old)'
                        }
                    },
                    y: {
                        beginAtZero: true,
                        title: {
                            display: true,
                            text: 'Maternal Counts'
                        }
                    }
                }
            }
        });
    });
</script>