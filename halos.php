<?php
    require_once "db/conn.php";

    $conn = new Connection();       // get Connection, Do Not Delete!
    $pdo = $conn->getConnection();

    // Total RUN
    $stmt = $pdo->query("SELECT DISTINCT RUN FROM result_halos");
    $run_count = $stmt->rowCount();

    // Total Sample
    $stmt = $pdo->query("SELECT COUNT(*) FROM result_halos WHERE Patient_Name NOT IN ('Positive Control', 'Negative Control')");
    $sample_count = $stmt->fetchColumn();

    // Total Sample
    $stmt = $pdo->query("SELECT COUNT(Sample_QC) FROM mlsi_nipt.result_halos WHERE Sample_QC = 'Fail' AND Patient_Name NOT IN ('Positive Control', 'Negative Control')");
    $fail_count = $stmt->fetchColumn();



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

    // 2. Prepare arrays for the Line Chart
    $run_labels = [];
    $fail_counts = [];

    foreach ($rows as $row) {
        $run_labels[] = $row['RUN'];
        $fail_counts[] = (int)($row['fail_count'] ?? 0);
    }


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <link rel="stylesheet" href="assets/datatables/datatables.css">
    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <link rel="stylesheet" href="css/main.css">
    <script src="assets/jquery.min.js"></script>
    <script src="assets/chart.umd.min.js"></script>
    <script src="assets/bootstrap.bundle.min.js"></script>
    <script src="assets/datatables/datatables.js"></script>

</head>
<body>


    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
        <div class="container">
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTogglerDemo02" aria-controls="navbarTogglerDemo02" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link me-2" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link me-2" href="halos.php">Maternal Data</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link me-2 active" aria-current="page" href="#">Platform Performance</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link me-2" href="#">About</a>
                    </li>
                </ul>
                <div class="text-white">
                    <div class="" style="font-size:14px; line-height:1.2;">
                        Genomics Medicine Center
                    </div>
                    <div class="" style="font-size:12px; line-height:1.2;">
                        Medical Life Science Institute, Department of Medical Science, MOPH Thailand
                    </div>
                </div>
            </div>
        </div>
    </nav>


<section class="container mt-4 position-relative">
    <div class="d-flex align-items-center">
        <img src="img/logo/logo_dmsc.png" alt="DMSc" class="me-3" style="height:85px;">
        <img src="img/logo/logo_gmc.png" alt="GMC" class="me-3" style="height:80px;">
        <div class="">
            <div class="fw-semibold" style="font-size:22px; line-height:1.2;">
                DMSc-NIPT Screening Platform
            </div>
            <div class="" style="font-size:17px; line-height:1.2;">
                Non-Invasive Prenatal Testing Public Dashboard Version 1.00
            </div>
        </div>
        <img src="img/hero_long2_transparent.png" alt="Description" class="outside-image">
    </div>
</section>

<style>
.outside-image {
    position: absolute;
    right: -36px;
    height: 150px;
    z-index: -1;
    -webkit-mask-image: linear-gradient(to left, black 0%, transparent 100%);
    mask-image: linear-gradient(to left, black 0%, transparent 100%);
}

@media (max-width: 1000px) {
    .outside-image {
        display: none;
    }
}
</style>


    <section class="container mt-4">
        <div class="row">
            <div class="col-md-3">
                <label for="exampleDataList" class="form-label small">Select RUN<span class="text-danger"> *</span></label>
                <select class="form-select form-select-sm fw-light" id="exampleDataList" name="" required>
                    <option value="" selected disabled>...</option>
                <?php
                    $stmt = $pdo->query("SELECT DISTINCT RUN FROM result_halos");
                    foreach ($stmt as $row):
                        echo '<option class="fw-light" value="' . $row['RUN'] . '">' . $row['RUN'] . '</option>';
                    endforeach;
                ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="exampleDataList" class="form-label small">Sample QC<span class="text-danger"> *</span></label>
                <select class="form-select form-select-sm" id="exampleDataList" name="" required>
                    <option value="" selected disabled>...</option>
                    <option value="Pass" class="fw-light">Pass</option>
                    <option value="Fail" class="fw-light">Fail</option>
                </select>
            </div>
            <div class="col-md-2">
                <label for="exampleDataList" class="form-label small">Operation Advice<span class="text-danger"> *</span></label>
                <select class="form-select form-select-sm fw-light" id="exampleDataList" name="" required>
                    <option value="" selected disabled>...</option>
                <?php
                    $stmt = $pdo->query("SELECT DISTINCT OperationAdvice FROM result_halos");
                    foreach ($stmt as $row):
                        echo '<option class="fw-light" value="' . $row['OperationAdvice'] . '">' . $row['OperationAdvice'] . '</option>';
                    endforeach;
                ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="exampleDataList" class="form-label small">Fetal Gender<span class="text-danger"> *</span></label>
                <select class="form-select form-select-sm" id="exampleDataList" name="" required>
                    <option value="" selected disabled>...</option>
                    <option value="Male" class="fw-light">Male</option>
                    <option value="Female" class="fw-light">Female</option>
                </select>
            </div>
            <div class="col-md-3">
                <label for="" class="form-label small opacity-0">Fetal Gender</label>
                <!-- <button type="submit" name="" class="btn btn-sm btn-gmc w-100">ค้นหา</button> -->
                <div class="btn-group w-100" role="group" aria-label="Basic example">
                    <button type="submit" class="btn btn-sm btn-gmc w-75">Search</button>
                    <button type="submit" class="btn btn-sm btn-secondary w-25">Reset</button>
                </div>
            </div>
        </div>
    </section>



    <section class="container my-4">
        <div class="row row-cols-1 row-cols-md-2 row-cols-xl-4">
            <div class="col">
                <div class="card border-start border-0 border-3 border-info shadow-sm">
                    <div class="d-flex align-items-center justify-content-between p-3">
                        <div>
                            <p class="mb-0 text-secondary">Total RUN</p>
                            <h3 class="my-1 text-info"><?php echo $run_count; ?></h3>
                            <p class="mb-0 text-success"><i class="fa-solid fa-arrow-trend-up" style="color: rgb(99, 230, 190);"></i> +2.5% from last week</p>
                        </div>
                        <div>
                            <img src="img/icon/sequencing.png" style="height: 60px;" alt="">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card border-start border-0 border-3 border-info shadow-sm">
                    <div class="d-flex align-items-center justify-content-between p-3">
                        <div>
                            <p class="mb-0 text-secondary">Total Sample</p>
                            <h3 class="my-1 text-info"><?php echo $sample_count; ?></h3>
                            <p class="mb-0 text-success"><i class="fa-solid fa-arrow-trend-up" style="color: rgb(99, 230, 190);"></i> +2.5% from last week</p>
                        </div>
                        <div>
                            <img src="img/icon/sample.png" style="height: 60px;" alt="">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card border-start border-0 border-3 border-danger shadow-sm">
                    <div class="d-flex align-items-center justify-content-between p-3">
                        <div>
                            <p class="mb-0 text-secondary">Fail Rate</p>
                            <h3 class="my-1 text-danger"><?php echo $fail_count . ' ('. round(($fail_count / $sample_count) * 100, 2) .'%)'; ?></h3>
                            <p class="mb-0 text-danger">Most type of Fail : XX</p>
                        </div>
                        <div>
                            <img src="img/icon/close.png" style="height: 60px;" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>




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







<section class="container bg-white shadow-sm rounded">
    <div class="table-responsive py-2">
        <table class="table table-hover table-striped display compact small" id="myTable2">
            <thead>
                <tr>
                    <th class="fw-light text-center" scope="col">RUN</th>
                    <th class="fw-light" scope="col">Sample No.</th>
                    <th class="fw-light" scope="col">Sample QC.</th>
                    <th class="fw-light" scope="col">Fail Type</th>
                    <th class="fw-light" scope="col">Operation Advice</th>
                    <th class="fw-light" scope="col">CHR_13</th>
                    <th class="fw-light" scope="col">CHR_18</th>
                    <th class="fw-light" scope="col">CHR_21</th>
                    <th class="fw-light" scope="col">Sex_CHR</th>
                    <th class="fw-light" scope="col">Comment</th>
                    <th class="fw-light" scope="col">Fetal Fraction</th>
                    <th class="fw-light" scope="col">Gender</th>
                </tr>
            </thead>
            <tbody class="table-group-divider">
                <?php
                    $stmt = $pdo->query("SELECT RUN, Sample_No, Sample_QC, Reason_for_quality_control_failure, OperationAdvice,
                                                `Test(chr13)`, `Test(chr18)`, `Test(chr21)`, `Test(Sex_chr)`, Comment,
                                                `Fetal_Fract_(%)`, Gender
                                        FROM result_halos WHERE Patient_Name NOT IN ('Positive Control', 'Negative Control')");
                    $halos = $stmt->fetchAll();

                    if (!$halos) {
                        echo "<p><td colspan='10' class='text-center'>ไม่พบข้อมูล</td></p>";
                    } else {
                        foreach ($halos as $halo) {
                ?>
                <tr>
                    <td><?php echo $halo['RUN']; ?></td>
                    <td><?php echo $halo['Sample_No']; ?></td>
                    <td><?php echo $halo['Sample_QC']; ?></td>
                    <td><?php echo $halo['Reason_for_quality_control_failure']; ?></td>
                    <td><?php echo $halo['OperationAdvice']; ?></td>
                    <td><?php echo $halo['Test(chr13)']; ?></td>
                    <td><?php echo $halo['Test(chr18)']; ?></td>
                    <td><?php echo $halo['Test(chr21)']; ?></td>
                    <td><?php echo $halo['Test(Sex_chr)']; ?></td>
                    <td><?php echo $halo['Comment']; ?></td>
                    <td><?php echo $halo['Fetal_Fract_(%)']; ?></td>
                    <td><?php echo $halo['Gender']; ?></td>
                </tr>
                <?php } } ?>
            </tbody>
        </table>
    </div>

</section>


<script>
    var table2 = $('#myTable2').DataTable({
                    'paging': true,
                    pageLength: 100
                });

    var length = table2.rows().count();;
    document.getElementById("rowcount").innerHTML = '(' + length + ' Items)';
</script>


<script type="module">


document.addEventListener("DOMContentLoaded", function() {
    // 3. Extract mapped database metrics safely via json_encode
    const chartLabels = <?php echo json_encode($run_labels); ?>;
    const chartDataValues = <?php echo json_encode($fail_counts); ?>;

    const ctx = document.getElementById('runFailureChart');
    if (!ctx) return;

    // 4. Initialize Line Chart
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



    
</body>
</html>