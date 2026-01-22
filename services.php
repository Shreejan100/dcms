<?php
session_start();
require_once 'config/database.php';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Our Services - Nagarik Dental</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="css/modern-style.css">
</head>
<body>
    <!-- Navigation Bar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-white fixed-top shadow-sm">
        <div class="container">
            <a class="navbar-brand fw-bold" href="index.php">
                <span class="text-primary">Nagarik</span> Dental
            </a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="services.php">Services</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="find-dentist.php">Our Dentists</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="index.php#contact">Contact</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                                <?php echo htmlspecialchars($_SESSION['username']); ?>
                            </a>
                            <ul class="dropdown-menu" aria-labelledby="navbarDropdown">
                                <li><a class="dropdown-item" href="<?php echo $_SESSION['role']; ?>/dashboard.php">Dashboard</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="auth/logout.php">Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="weblogin.php">Login</a>
                        </li>
                        <li class="nav-item dropdown ms-2">
                            <a class="nav-link dropdown-toggle btn btn-primary text-white px-4 rounded-pill" href="#" id="registerDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                Register
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="registerDropdown">
                                <li><a class="dropdown-item" href="auth/register.php"><i class="fas fa-user-plus me-2"></i>Patient Registration</a></li>
                                <li><a class="dropdown-item" href="auth/register_dentist.php"><i class="fas fa-user-md me-2"></i>Dentist Registration</a></li>
                            </ul>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Page Header -->
    <div class="bg-primary text-white py-5">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-8">
                    <h1 class="display-4 fw-bold mb-3">Our Dental Services</h1>
                    <p class="lead mb-0">Comprehensive dental care for all your needs from our expert specialists</p>
                </div>
                <div class="col-lg-4 d-none d-lg-block">
                    <img src="assets/images/service-hero.png" alt="Dental Services" class="img-fluid rounded-circle" style="max-height: 200px;">
                </div>
            </div>
        </div>
    </div>

    <!-- Introduction Section -->
    <section class="py-5">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="text-center mb-5">
                        <h2 class="display-5 fw-bold">Expert Dental Care</h2>
                        <p class="lead text-muted">At Nagarik Dental, we offer a wide range of dental services provided by specialized professionals. Our team of experts is committed to delivering the highest quality of care using the latest techniques and technologies in modern dentistry.</p>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <!-- Services Section -->
    <section class="py-5 bg-light">
        <div class="container">
            <!-- General Dentist -->
            <div class="service-item mb-5">
                <div class="row align-items-center flex-lg-row-reverse">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <img src="assets/images/service-general.jpg" alt="General Dentistry" class="img-fluid rounded-4 shadow">
                    </div>
                    <div class="col-lg-6">
                        <div class="service-content">
                            <div class="service-icon mb-3">
                                <i class="fas fa-check-circle fa-3x text-primary"></i>
                            </div>
                            <h3 class="mb-3">General Dentist</h3>
                            <p class="lead mb-3">Your primary dental care provider for overall oral health</p>
                            <p class="mb-4">General dentists are the primary dental care providers for patients of all ages. They provide preventive care and treatment for a wide variety of dental issues. Rather than specializing in one area, general dentists can provide a wide range of services for you and your family.</p>
                            <h5 class="mb-2">Services Include:</h5>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Regular dental check-ups and cleanings</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Fillings and cavity repair</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Root canals (basic)</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Tooth extractions</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Preventive dental education</li>
                            </ul>
                            <a href="find-dentist.php?specialization=General Dentist" class="btn btn-primary rounded-pill px-4">Find a General Dentist</a>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-5">
            
            <!-- Orthodontist -->
            <div class="service-item mb-5">
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <img src="assets/images/service-ortho.jpg" alt="Orthodontics" class="img-fluid rounded-4 shadow">
                    </div>
                    <div class="col-lg-6">
                        <div class="service-content">
                            <div class="service-icon mb-3">
                                <i class="fas fa-teeth fa-3x text-primary"></i>
                            </div>
                            <h3 class="mb-3">Orthodontist</h3>
                            <p class="lead mb-3">Specialists in straightening teeth and correcting misaligned jaws</p>
                            <p class="mb-4">Orthodontists focus on diagnosing, preventing, and treating dental and facial irregularities. They specialize in correcting misaligned teeth and jaws using various appliances such as braces, clear aligners, and retainers.</p>
                            <h5 class="mb-2">Services Include:</h5>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Traditional metal braces</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Ceramic (clear) braces</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Invisible aligners (Invisalign)</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Retainers</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Jaw alignment</li>
                            </ul>
                            <a href="find-dentist.php?specialization=Orthodontist" class="btn btn-primary rounded-pill px-4">Find an Orthodontist</a>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <!-- Endodontist -->
            <div class="service-item mb-5">
                <div class="row align-items-center flex-lg-row-reverse">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <img src="assets/images/service-endo.jpg" alt="Endodontics" class="img-fluid rounded-4 shadow">
                    </div>
                    <div class="col-lg-6">
                        <div class="service-content">
                            <div class="service-icon mb-3">
                                <i class="fas fa-tooth fa-3x text-primary"></i>
                            </div>
                            <h3 class="mb-3">Endodontist</h3>
                            <p class="lead mb-3">Experts in treating dental pulp and root canal procedures</p>
                            <p class="mb-4">Endodontists are dental specialists who focus on procedures involving the inside of teeth. They are experts in diagnosing tooth pain and performing root canal treatments and other procedures related to the interior of the tooth.</p>
                            <h5 class="mb-2">Services Include:</h5>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Root canal therapy</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Endodontic retreatment</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Apicoectomy (root-end surgery)</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Treatment of cracked teeth</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Dental trauma treatment</li>
                            </ul>
                            <a href="find-dentist.php?specialization=Endodontist" class="btn btn-primary rounded-pill px-4">Find an Endodontist</a>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <!-- Periodontist -->
            <div class="service-item mb-5">
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <img src="assets/images/service-perio.jpg" alt="Periodontics" class="img-fluid rounded-4 shadow">
                    </div>
                    <div class="col-lg-6">
                        <div class="service-content">
                            <div class="service-icon mb-3">
                                <i class="fas fa-notes-medical fa-3x text-primary"></i>
                            </div>
                            <h3 class="mb-3">Periodontist</h3>
                            <p class="lead mb-3">Specialists in treating gum disease and supporting structures of teeth</p>
                            <p class="mb-4">Periodontists specialize in the prevention, diagnosis, and treatment of periodontal (gum) disease and the placement of dental implants. They are experts in treating oral inflammation and are trained in performing cosmetic periodontal procedures.</p>
                            <h5 class="mb-2">Services Include:</h5>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Scaling and root planing (deep cleaning)</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Gum surgery</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Dental implant placement</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Gum grafts</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Crown lengthening</li>
                            </ul>
                            <a href="find-dentist.php?specialization=Periodontist" class="btn btn-primary rounded-pill px-4">Find a Periodontist</a>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <!-- Prosthodontist -->
            <div class="service-item mb-5">
                <div class="row align-items-center flex-lg-row-reverse">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <img src="assets/images/service-prostho.jpg" alt="Prosthodontics" class="img-fluid rounded-4 shadow">
                    </div>
                    <div class="col-lg-6">
                        <div class="service-content">
                            <div class="service-icon mb-3">
                                <i class="fas fa-teeth-open fa-3x text-primary"></i>
                            </div>
                            <h3 class="mb-3">Prosthodontist</h3>
                            <p class="lead mb-3">Experts in replacing missing teeth and restoring natural appearance</p>
                            <p class="mb-4">Prosthodontists specialize in the restoration and replacement of missing teeth and dental structures. They are highly trained in cosmetic dentistry, dental implants, crowns, bridges, dentures, and treating complex dental conditions.</p>
                            <h5 class="mb-2">Services Include:</h5>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Dental crowns and bridges</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Complete and partial dentures</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Implant-supported prosthetics</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Full mouth reconstruction</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Temporomandibular Joint Disorder (TMJ) treatment</li>
                            </ul>
                            <a href="find-dentist.php?specialization=Prosthodontist" class="btn btn-primary rounded-pill px-4">Find a Prosthodontist</a>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <!-- Pediatric Dentist -->
            <div class="service-item mb-5">
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <img src="assets/images/service-pedo.jpg" alt="Pediatric Dentistry" class="img-fluid rounded-4 shadow">
                    </div>
                    <div class="col-lg-6">
                        <div class="service-content">
                            <div class="service-icon mb-3">
                                <i class="fas fa-baby fa-3x text-primary"></i>
                            </div>
                            <h3 class="mb-3">Pediatric Dentist</h3>
                            <p class="lead mb-3">Specialists in children's dental health from infancy through adolescence</p>
                            <p class="mb-4">Pediatric dentists are dedicated to the oral health of children from infancy through the teen years. They have the experience and qualifications to care for a child's teeth, gums, and mouth throughout the various stages of childhood.</p>
                            <h5 class="mb-2">Services Include:</h5>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Infant oral health exams</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Preventive dental care</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Habit counseling (pacifier use, thumb sucking)</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Early assessment for orthodontic treatment</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Treatment of dental injuries</li>
                            </ul>
                            <a href="find-dentist.php?specialization=Pediatric Dentist" class="btn btn-primary rounded-pill px-4">Find a Pediatric Dentist</a>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <!-- Cosmetic Dentist -->
            <div class="service-item mb-5">
                <div class="row align-items-center flex-lg-row-reverse">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <img src="assets/images/service-cosmetic.jpg" alt="Cosmetic Dentistry" class="img-fluid rounded-4 shadow">
                    </div>
                    <div class="col-lg-6">
                        <div class="service-content">
                            <div class="service-icon mb-3">
                                <i class="fas fa-smile fa-3x text-primary"></i>
                            </div>
                            <h3 class="mb-3">Cosmetic Dentist</h3>
                            <p class="lead mb-3">Experts in improving the appearance of your smile</p>
                            <p class="mb-4">Cosmetic dentists focus on improving the appearance of your teeth, mouth, and smile. While cosmetic dentistry procedures are often elective rather than essential, some cases also provide restorative benefits.</p>
                            <h5 class="mb-2">Services Include:</h5>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Teeth whitening</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Dental veneers</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Dental bonding</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Smile makeovers</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Gum contouring</li>
                            </ul>
                            <a href="find-dentist.php?specialization=Cosmetic Dentist" class="btn btn-primary rounded-pill px-4">Find a Cosmetic Dentist</a>
                        </div>
                    </div>
                </div>
            </div>

            <hr class="my-5">

            <!-- Oral and Maxillofacial Surgeon -->
            <div class="service-item mb-5">
                <div class="row align-items-center">
                    <div class="col-lg-6 mb-4 mb-lg-0">
                        <img src="assets/images/service-oms.jpg" alt="Oral Surgery" class="img-fluid rounded-4 shadow">
                    </div>
                    <div class="col-lg-6">
                        <div class="service-content">
                            <div class="service-icon mb-3">
                                <i class="fas fa-user-md fa-3x text-primary"></i>
                            </div>
                            <h3 class="mb-3">Oral and Maxillofacial Surgeon</h3>
                            <p class="lead mb-3">Specialists in surgeries of the mouth, face, and jaw</p>
                            <p class="mb-4">Oral and maxillofacial surgeons are specialists who treat conditions, defects, injuries, and aesthetic aspects of the mouth, teeth, jaws, and face. They are trained to administer anesthesia and provide care in an office setting.</p>
                            <h5 class="mb-2">Services Include:</h5>
                            <ul class="list-unstyled mb-4">
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Tooth extractions (including wisdom teeth)</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Dental implant placement</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Jaw surgery</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Treatment of facial trauma</li>
                                <li class="mb-2"><i class="fas fa-check-circle text-primary me-2"></i> Oral pathology (diagnosing and treating oral diseases)</li>
                            </ul>
                            <a href="find-dentist.php?specialization=Oral Surgeon" class="btn btn-primary rounded-pill px-4">Find an Oral Surgeon</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>



    <!-- Bootstrap JS -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
