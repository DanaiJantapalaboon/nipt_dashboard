<?php
    require_once "db/conn.php";

    $conn = new Connection();       // get Connection, Do Not Delete!
    $pdo = $conn->getConnection();

    // Total Sample Testing
    $stmt = $pdo->query("SELECT COUNT(*) FROM result_info");
    $maternal_count = $stmt->fetchColumn();

    // Total RUN
    $stmt = $pdo->query("SELECT DISTINCT RUN FROM result_info");
    $run_count = $stmt->rowCount();

    // Maternal Average Age
    $stmt = $pdo->query("SELECT AVG((YEAR(CURDATE()) + 543) - DOB_Year) FROM result_info");
    $average_age = $stmt->fetchColumn();

    // Maternal Average BMI
    $stmt = $pdo->query("SELECT AVG(BMI) FROM result_info");
    $average_bmi = $stmt->fetchColumn();

    // 1. Run one query to get both counts
    $sql = "SELECT 
                SUM(CASE WHEN GA_type = 'ครรภ์แฝด' THEN 1 ELSE 0 END) AS twins,
                SUM(CASE WHEN GA_type = 'ครรภ์เดี่ยว' THEN 1 ELSE 0 END) AS singleton
            FROM result_info";

    $stmt = $pdo->query($sql);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Extract counts (default to 0 if null)
    $twins_count = $row['twins'] ?? 0;
    $singleton_count = $row['singleton'] ?? 0;

    // 3. Calculate Total and Percentages
    $total = $twins_count + $singleton_count;
    $twins_percent = ($twins_count / $total) * 100;
    $singleton_percent = ($singleton_count / $total) * 100;

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
                AND Comment NOT IN ('Relibrary')
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
                AND Comment NOT IN ('Relibrary')
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

    // DOB Year
    $sql = "SELECT (2569 - DOB_Year) AS AGE, COUNT(*) AS total_count FROM result_info 
            WHERE DOB_Year IS NOT NULL
            GROUP BY AGE 
            ORDER BY AGE ASC";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. Separate into unique arrays for Chart.js labels (X-axis) and data points (Y-axis)
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <link rel="stylesheet" href="css/main.css">
    <script src="assets/chart.umd.min.js"></script>
    <script src="assets/bootstrap.bundle.min.js"></script>
    <script src="assets/jquery.min.js"></script>
</head>
<body>


    <nav class="navbar navbar-expand-lg navbar-dark shadow-sm">
        <div class="container">
            <!-- <a class="navbar-brand" href="#">
                <img src="/docs/5.3/assets/brand/bootstrap-logo.svg" width="30" height="24" class="d-inline-block align-text-top">
                Bootstrap
            </a> -->
            <!-- <a class="navbar-brand d-flex align-items-center" href="#">
                <img src="img/logo/logo_dmsc.png" alt="DMSc" class="me-3 d-inline-block align-text-top" style="height:44px;">
                <img src="img/logo/logo_gmc.png" alt="GMC" class="me-3 d-inline-block align-text-top" style="height:39px;">
            </a> -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarTogglerDemo02" aria-controls="navbarTogglerDemo02" aria-expanded="false" aria-label="Toggle navigation">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarTogglerDemo02">
                <ul class="navbar-nav me-auto mb-2 mb-lg-0">
                    <li class="nav-item">
                        <a class="nav-link me-2 active" aria-current="page" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link me-2" href="#">Patient Info</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link me-2" href="halos.php">HALOS</a>
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
                <label for="exampleDataList" class="form-label small">Select Hospital<span class="text-danger"> *</span></label>
                <input class="form-control form-control-sm fw-light" list="datalistOptions" id="exampleDataList" name="" placeholder="..." required>
                <datalist id="datalistOptions">
                <?php
                    $stmt = $pdo->query("SELECT DISTINCT institute FROM result_info");
                    foreach ($stmt as $row):
                        echo '<option value="' . $row['institute'] . '">';
                    endforeach;
                ?>
                </datalist>
            </div>
            <div class="col-md-2">
                <label for="exampleDataList" class="form-label small">Select RUN<span class="text-danger"> *</span></label>
                <select class="form-select form-select-sm fw-light" id="exampleDataList" name="" required>
                    <option value="" selected disabled>...</option>
                <?php
                    $stmt = $pdo->query("SELECT DISTINCT RUN FROM result_info");
                    foreach ($stmt as $row):
                        echo '<option class="fw-light" value="' . $row['RUN'] . '">' . $row['RUN'] . '</option>';
                    endforeach;
                ?>
                </select>
            </div>
            <div class="col-md-2">
                <label for="exampleDataList" class="form-label small">Pregnancy Type<span class="text-danger"> *</span></label>
                <select class="form-select form-select-sm fw-light" id="exampleDataList" name="" required>
                    <option value="" selected disabled>...</option>
                    <option value="ครรภ์เดี่ยว" class="fw-light">Singleton</option>
                    <option value="ครรภ์แฝด" class="fw-light">Twins</option>
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
                    <button type="submit" class="btn btn-sm btn-dark w-25">Reset</button>
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
                            <p class="mb-0 text-secondary">Total Maternal Screening</p>
                            <h3 class="my-1 text-info"><?php echo $maternal_count; ?></h3>
                            <p class="mb-0 text-success"><i class="fa-solid fa-arrow-trend-up" style="color: rgb(99, 230, 190);"></i> +2.5% from last week</p>
                        </div>
                        <div>
                            <img src="img/icon/nipt.png" style="height: 60px;" alt="">
                        </div>
                    </div>
                </div>
            </div>
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
                            <p class="mb-0 text-secondary">Maternal Average Age</p>
                            <h3 class="my-1 text-info"><?php echo round($average_age, 1); ?></h3>
                            <p class="mb-0 text-success"><i class="fa-solid fa-arrow-trend-up" style="color: rgb(99, 230, 190);"></i> +2.5% from last week</p>
                        </div>
                        <div>
                            <img src="img/icon/maternal.png" style="height: 60px;" alt="">
                        </div>
                    </div>
                </div>
            </div>
            <div class="col">
                <div class="card border-start border-0 border-3 border-info shadow-sm">
                    <div class="d-flex align-items-center justify-content-between p-3">
                        <div>
                            <p class="mb-0 text-secondary">Maternal Average BMI</p>
                            <h3 class="my-1 text-info"><?php echo round($average_bmi, 1); ?></h3>
                            <p class="mb-0 text-success"><i class="fa-solid fa-arrow-trend-up" style="color: rgb(99, 230, 190);"></i> +2.5% from last week</p>
                        </div>
                        <div>
                            <img src="img/icon/obesity.png" style="height: 60px;" alt="">
                        </div>
                    </div>
                </div>
            </div>
            <!-- <div class="col">
                <div class="card border-start border-0 border-3 border-info shadow-sm">
                    <div class="d-flex align-items-center justify-content-between p-3">
                        <div>
                            <p class="mb-0 text-secondary">Singleton / Twins</p>
                            <h3 class="my-1 text-info"><?php echo $singleton_count.' / '. $twins_count; ?></h3>
                            <p class="mb-0 text-success"><i class="fa-solid fa-arrow-trend-up" style="color: rgb(99, 230, 190);"></i> <?php 
  //echo number_format($singleton_percent, 2) . '% / ' . number_format($twins_percent, 2) . '%'; 
?></p>
                        </div>
                        <div>
                            <i class="fa-solid fa-user fa-2xl" style="color: rgb(116, 192, 252);"></i>
                        </div>
                    </div>
                </div>
            </div> -->
        </div>
    </section>


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
                            <th scope="col" class="fw-light text-center">Total</th>
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
                            <td class="text-danger text-center"><?php echo round(($row_male['xxx_count'] / $maternal_count) * 100, 4); ?></td>
                            <td class="text-secondary text-center"><?php echo $row_female['xxx_count']; ?></td>
                            <td class="text-secondary text-center">1 : <?php echo round($maternal_count / ($row_female['xxx_count'] + $row_male['xxx_count']), 0); ?></td>
                        </tr>
                        <tr>
                            <td class="text-secondary">XXY (Klinefelter Syndrome)</td>
                            <td class="text-primary text-center"><?php echo $row_male['xxy_count']; ?></td>
                            <td class="text-primary text-center"><?php echo round(($row_male['xxy_count'] / $maternal_count) * 100, 4); ?></td>
                            <td class="text-danger text-center">-</td>
                            <td class="text-danger text-center">-</td>
                            <td class="text-secondary text-center">-</td>
                            <td class="text-secondary text-center">1 : <?php echo round($maternal_count / ($row_female['xxy_count'] + $row_male['xxy_count']), 0); ?></td>
                        </tr>
                        <tr>
                            <td class="text-secondary">XYY (Jacobs syndrome)</td>
                            <td class="text-primary text-center"><?php echo $row_male['xyy_count']; ?></td>
                            <td class="text-primary text-center"><?php echo round(($row_male['xyy_count'] / $maternal_count) * 100, 4); ?></td>
                            <td class="text-danger text-center">-</td>
                            <td class="text-danger text-center">-</td>
                            <td class="text-secondary text-center">-</td>
                            <td class="text-secondary text-center">1 : <?php echo round($maternal_count / ($row_female['xyy_count'] + $row_male['xyy_count']), 0); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>


    <section class="container mt-3">
        <div class="row">
            <div class="col-md-6">
                <canvas id="ageDistributionChart"></canvas>
            </div>
            <div class="col-md-6">
                <p><b>Table 2. </b>DMSc-NIPT Maternal Age Distribution</p>
                <table class="table table-sm table-hover" style="--bs-table-bg: transparent;">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="fw-light">Age Groups</th>
                            <th scope="col" class="fw-light text-center">Counts</th>
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

<script type="module">


document.addEventListener("DOMContentLoaded", function() {
    const ageLabels = <?php echo json_encode($ages); ?>;
    const distributionData = <?php echo json_encode($counts); ?>;

    const ctx = document.getElementById('ageDistributionChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'bar', 
        data: {
            labels: ageLabels, // Ages line up cleanly on the X-axis
            datasets: [{
                label: 'Maternal Counts',
                data: distributionData, // Heights of the bars show density
                backgroundColor: 'rgba(75, 192, 192, 0.6)', // Clean teal fill
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
                    text: 'DMSc-NIPT Bell Curve of Maternal Age Distribution',
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

    
</body>
</html>