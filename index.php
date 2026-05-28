<?php
    require_once "db/conn.php";

    $conn = new Connection();
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
    $stmt = $pdo->query("SELECT institute, COUNT(institute) AS institute_count FROM mlsi_nipt.result_info GROUP BY institute ORDER BY institute_count DESC LIMIT 1");
    $top_institute = $stmt->fetch(PDO::FETCH_ASSOC);

    // 1. Run one query to get both counts
    $sql = "SELECT 
                SUM(CASE WHEN GA_type = 'ครรภ์แฝด' THEN 1 ELSE 0 END) AS twins,
                SUM(CASE WHEN GA_type = 'ครรภ์เดี่ยว' THEN 1 ELSE 0 END) AS singleton
            FROM result_info";

    $stmt = $pdo->query($sql);
    $rows = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Extract counts (default to 0 if null)
    $twins_count = (int)($rows['twins'] ?? 0);
    $singleton_count = (int)($rows['singleton'] ?? 0);


?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Document</title>

    <link rel="stylesheet" href="assets/bootstrap.min.css">
    <link rel="stylesheet" href="css/main.css">
    <script src="assets/jquery.min.js"></script>
    <script src="assets/chart.umd.min.js"></script>
    <script src="assets/bootstrap.bundle.min.js"></script>
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
                        <a class="nav-link me-2 active" aria-current="page" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link me-2" href="#">Maternal Data</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link me-2" href="halos.php">Platform Performance</a>
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
            <div class="col-md-3">
                <label for="exampleDataList" class="form-label small">Select RUN<span class="text-danger"> *</span></label>
                <input class="form-control form-control-sm fw-light" list="datalistOptions2" id="exampleDataList" name="" placeholder="..." required>
                <datalist id="datalistOptions2">
                <?php
                    $stmt = $pdo->query("SELECT DISTINCT RUN FROM result_info");
                    foreach ($stmt as $row):
                        echo '<option class="fw-light" value="' . $row['RUN'] . '">' . $row['RUN'] . '</option>';
                    endforeach;
                ?>
                </datalist>
            </div>
            <div class="col-md-3">
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
                    <button type="reset" class="btn btn-sm btn-secondary w-25">Reset</button>
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
                            <p class="mb-0 text-success">Average Gestation Age : 00 weeks </p>
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
                            <p class="mb-0 text-success">Latest RUN : </p>
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
                            <p class="mb-0 text-success">MAX : , MIN : </p>
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
                            <p class="mb-0 text-secondary">Top Samples Sender</p>
                            <h3 class="my-1 text-info"><?php echo $top_institute['institute_count']; ?></h3>
                            <p class="mb-0 text-success"><i class="fa-solid fa-arrow-trend-up" style="color: rgb(99, 230, 190);"></i> <?php echo $top_institute['institute']; ?></p>
                        </div>
                        <div>
                            <img src="img/icon/hospital.png" style="height: 60px;" alt="">
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>


    <?php include_once 'db/query_nipt_highrisk.php'; ?>
    <?php include_once 'db/query_nipt_agedist.php'; ?>





    <section class="container mt-3">
        <div class="row">
            <div class="col-md-6 mx-auto" style="max-width: 400px;">
                <canvas id="gestationTypePieChart"></canvas>
            </div>
            <div class="col-md-6">
                <p><b>Table 3. </b>Maternal Gestation Type</p>
                <table class="table table-sm table-hover" style="--bs-table-bg: transparent;">
                    <thead class="table-light">
                        <tr>
                            <th scope="col" class="fw-light">Gestation Type</th>
                            <th scope="col" class="fw-light text-center"><i>N</i></th>
                            <th scope="col" class="fw-light text-center">%</th>
                            <th scope="col" class="fw-light text-center">Ratio</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td class="text-secondary">Singleton</td>
                            <td class="text-secondary text-center"><?php echo $singleton_count; ?></td>
                            <td class="text-secondary text-center"><?php echo round($singleton_count / ($singleton_count + $twins_count)*100, 2); ?></td>
                            <td class="text-secondary text-center">1 : <?php echo round(($singleton_count + $twins_count) / $singleton_count, 0); ?></td>
                        </tr>
                        <tr>
                            <td class="text-secondary">Twins</td>
                            <td class="text-secondary text-center"><?php echo $twins_count; ?></td>
                            <td class="text-secondary text-center"><?php echo round($twins_count / ($singleton_count + $twins_count)*100, 2); ?></td>
                            <td class="text-secondary text-center">1 : <?php echo round(($singleton_count + $twins_count) / $twins_count, 0); ?></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </section>



    <?php include_once 'db/query_nipt_institute.php'; ?>




    <style>
.footer {
    background: var(--gmc-darker-color);
    padding: 40px 0 20px;
    border-top: 1px solid #eee;
}

.footer-brand {
    font-size: 1.5rem;
    font-weight: 600;
    color: #333;
    margin-bottom: 15px;
}

.footer-text {
    color: #6c757d;
    font-size: 0.9rem;
}

.social-links {
    margin: 20px 0;
}

.social-link {
    color: #6c757d;
    margin-right: 20px;
    font-size: 1.2rem;
    transition: color 0.3s ease;
}

.social-link:hover {
    color: #0d6efd;
}

.footer-links {
    display: flex;
    justify-content: end;
    gap: 20px;
    margin: 0;
    padding: 0;
    list-style: none;
}

.footer-links a {
    color: #6c757d;
    text-decoration: none;
    font-size: 0.9rem;
    transition: color 0.3s ease;
}

.footer-links a:hover {
    color: #0d6efd;
}

.copyright {
    color: #6c757d;
    font-size: 0.85rem;
    margin-top: 20px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}
</style>

<footer class="footer">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-6">
                <div class="footer-brand text-white">Genomics Medicine Center, Medical Life Science Institute</div>
                <p class="footer-text text-white">ศูนย์การแพทย์จีโนมิกส์ สถาบันชีววิทยาศาสตร์ทางการแพทย์ เป็นหน่วยบริการด้านการตรวจวิเคราะห์ทางจีโนมิกส์ด้วยเทคโนโลยีขั้นสูง มีการศึกษาวิจัยเพื่อพัฒนางานด้าน Precision Medicine ด้านเภสัชพันธุศาสตร์ (Pharmacogenomics) โรคมะเร็ง โรคหายาก ที่เกี่ยวข้องกับพันธุกรรม จัดทำ National Genome Database และเป็นศูนย์ทรัพยากรชีวภาพที่ได้มาตรฐาน ISO 20387 ของกรมวิทยาศาสตร์การแพทย์</p>
                <div class="social-links">
                    <a href="#" class="social-link"><i class="fa-brands fa-facebook"></i></a>
                    <a href="#" class="social-link"><i class="fa-solid fa-envelope"></i></a>
                    <a href="#" class="social-link"><i class="fa-solid fa-globe"></i></a>
                </div>
            </div>
            <div class="col-md-6">
                <ul class="footer-links">
                    <li><a href="#">Home</a></li>
                    <li><a href="#">Platform Performance</a></li>
                    <li><a href="#">About</a></li>
                </ul>
            </div>
        </div>
        <div class="copyright text-center">
            © 2026 Genomics Medicine Center, Medical Life Science Institute. All rights reserved.
        </div>
    </div>
</footer>












<script type="module">

document.addEventListener("DOMContentLoaded", function() {
    const twins = <?php echo json_encode($twins_count); ?>;
    const singleton = <?php echo json_encode($singleton_count); ?>;

    const ctx = document.getElementById('gestationTypePieChart');
    if (!ctx) return;

    new Chart(ctx, {
        type: 'pie',
        data: {
            labels: ['Twins', 'Singleton'],
            datasets: [{
                data: [twins, singleton],
                backgroundColor: [
                    'rgba(255, 206, 86, 0.7)',
                    'rgba(153, 102, 255, 0.7)'
                ],
                borderColor: [
                    'rgba(255, 206, 86, 1)',
                    'rgba(153, 102, 255, 1)'
                ],
                borderWidth: 1
            }]
        },
        options: {
            responsive: true,
            plugins: {
                title: {
                    display: true,
                    text: 'DMSc-NIPT Gestation Type Screening',
                    font: { size: 14, weight: 'bold' }
                },
                legend: {
                    position: 'bottom' // Moves the labels below the chart for a cleaner look
                },
                tooltip: {
                    callbacks: {
                        label: function(context) {
                            const value = context.raw;
                            const total = context.dataset.data.reduce((a, b) => a + b, 0);
                            const percentage = total > 0 ? ((value / total) * 100).toFixed(1) : 0;
                            return ` ${context.label}: ${value} Cases (${percentage}%)`;
                        }
                    }
                }
            }
        }
    });
});

    
</script>





    
</body>
</html>