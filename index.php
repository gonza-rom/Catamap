<!DOCTYPE html>
<html lang="en">
<head>
	<meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="description" content="catamap">
    <meta name="author" content="Gonzalo Romero">
    
    <title>Catamap</title>
    <!-- font icons -->
    <link rel="stylesheet" href="assets/vendors/themify-icons/css/themify-icons.css">

    <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.css" rel="stylesheet">

    <link rel="stylesheet" href="assets/vendors/animate/animate.css">

    <link href="lib/owlcarousel/assets/owl.carousel.min.css" rel="stylesheet">

    <!-- Bootstrap + FoodHut main styles -->
	<link rel="stylesheet" href="assets/css/foodhut.css">
    
    <!-- Google Web Fonts -->
    <link rel="preconnect" href="https://fonts.gstatic.com">
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700&display=swap" rel="stylesheet"> 

    <!-- Font Awesome -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.10.0/css/all.min.css" rel="stylesheet">

    <!-- Libraries Stylesheet -->
    <link href="lib/tempusdominus/css/tempusdominus-bootstrap-4.min.css" rel="stylesheet" />

    <!-- Customized Bootstrap Stylesheet -->
    <link href="css/style.css" rel="stylesheet">
</head>
<body data-spy="scroll" data-target=".navbar" data-offset="40" id="home">
    
    <!-- Navbar -->
    <nav class="custom-navbar navbar navbar-expand-lg navbar-dark fixed-top" data-spy="affix" data-offset-top="10">
        <button class="navbar-toggler" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarSupportedContent">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="#inicio">Inicio</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#about">Sobre Nosotros</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#gallary">Destacados</a>
                </li>
            </ul>
            <a class="navbar-brand m-auto" href="#">
                <img src="img/CATAMAP.png" class="brand-img" alt="">
                <span class="brand-txt">CATAMAP</span>
            </a>
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link" href="#destinos">Destinos<span class="sr-only">(current)</span></a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#servicios">Servicios</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#testimonios">Testimonios</a>
                </li>
                <!-- Botón dinámico: cambia según estado de autenticación -->
                <li class="nav-item" id="authButtonContainer">
                    <a class="btn btn-primary ml-xl-4" data-toggle="modal" data-target="#loginModal">
                        Iniciar Sesión
                    </a>
                </li>
            </ul>
        </div>
    </nav>
    <!-- header -->
    <!-- Carousel Start -->
    <div id="inicio" class="container-fluid p-0">
        <div id="header-carousel" class="carousel slide" data-ride="carousel">
            <div class="carousel-inner">
                <div class="carousel-item active">
                    <img class="w-100" src="img/rutanartural.jpg" alt="Image">
                    <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                        <div class="p-3" style="max-width: 900px;">
                            <h4 class="text-white text-uppercase mb-md-3">Descubrí Catamarca</h4>
                            <h1 class="display-3 text-white mb-md-4">Explorá los rincones más increíbles de la provincia</h1>
                            <a href="./pages/mapa-catamarca.php" class="btn btn-primary py-md-3 px-md-5 mt-2">Ver Mapa</a>
                        </div>
                    </div>
                </div>
                <div class="carousel-item">
                    <img class="w-100" src="img/cuestaportezuelo.jpg" alt="Image">
                    <div class="carousel-caption d-flex flex-column align-items-center justify-content-center">
                        <div class="p-3" style="max-width: 900px;">
                            <h4 class="text-white text-uppercase mb-md-3">Turismo & Cultura</h4>
                            <h1 class="display-3 text-white mb-md-4">Viví la experiencia Catamarca con Catamap</h1>
                            <a href="./pages/mapa-catamarca.php" class="btn btn-primary py-md-3 px-md-5 mt-2">Ver Mapa</a>
                        </div>
                    </div>
                </div>
            </div>
            <a class="carousel-control-prev" href="#header-carousel" data-slide="prev">
                <div class="btn btn-dark" style="width: 45px; height: 45px;">
                    <span class="carousel-control-prev-icon mb-n2"></span>
                </div>
            </a>
            <a class="carousel-control-next" href="#header-carousel" data-slide="next">
                <div class="btn btn-dark" style="width: 45px; height: 45px;">
                    <span class="carousel-control-next-icon mb-n2"></span>
                </div>
            </a>
        </div>
    </div>
    <!-- Carousel End -->

    <!--  About Section  -->
    <!-- About Start -->
    <div class="container-fluid py-5">
        <div id="about" class="container pt-5">
            <div class="row">
                <div class="col-lg-6" style="min-height: 500px;">
                    <div class="position-relative h-100">
                        <img class="position-absolute w-100 h-100" src="img/about.jpg" style="object-fit: cover;">
                    </div>
                </div>
                <div class="col-lg-6 pt-5 pb-lg-5">
                    <div class="about-text bg-white p-4 p-lg-5 my-lg-5">
                        <h6 class="text-primary text-uppercase" style="letter-spacing: 5px;">Sobre Nosotros</h6>
                        <h1 class="mb-3">Conectamos personas con los lugares más bellos de Catamarca</h1>
                        <p>Catamap es una plataforma creada para explorar, conocer y disfrutar cada rincón de Catamarca. Desde sus paisajes naturales hasta su patrimonio histórico y cultural, te ayudamos a descubrir todo lo que la provincia tiene para ofrecer.</p>
                        <div class="row mb-4">
                            <div class="col-6">
                                <img class="img-fluid" src="img/bicicta.jpg" alt="">
                            </div>
                            <div class="col-6">
                                <img class="img-fluid" src="img/vistacta.jpg" alt="">
                            </div>
                        </div>
                        <a href="" class="btn btn-primary mt-1">Explorar Catamarca</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- About End -->

    <!--  gallary Section  -->
    <div id="gallary" class="text-center bg-dark text-light has-height-md middle-items wow fadeIn">
        <h2 class="section-title">Lugares Destacados de Catamarca</h2>
    </div>
    <div class="gallary row">
        <div class="col-sm-6 col-lg-3 gallary-item wow fadeIn">
            <img src="img-catamarca/tuneles-merced.JPG" alt="Lugar Turistico" class="gallary-img">
            <a href="#" class="gallary-overlay" data-img="img-catamarca/tuneles-merced.JPG">
                <i class="gallary-icon ti-plus"></i>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3 gallary-item wow fadeIn">
            <img src="img-catamarca/termas-de-fiambala.jpg" alt="Lugar Turistico" class="gallary-img">
            <a href="#" class="gallary-overlay" data-img="img-catamarca/termas-de-fiambala.jpg">
                <i class="gallary-icon ti-plus"></i>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3 gallary-item wow fadeIn">
            <img src="img-catamarca/salton-balcozna.JPG" alt="Lugar Turistico" class="gallary-img">
            <a href="#" class="gallary-overlay" data-img="img-catamarca/salton-balcozna.JPG">
                <i class="gallary-icon ti-plus"></i>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3 gallary-item wow fadeIn">
            <img src="img-catamarca/ruta-de-los-seismiles.jpg" alt="Lugar Turistico" class="gallary-img">
            <a href="#" class="gallary-overlay" data-img="img-catamarca/ruta-de-los-seismiles.jpg">
                <i class="gallary-icon ti-plus"></i>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3 gallary-item wow fadeIn">
            <img src="img-catamarca/Plaza_25_de_Mayo.jpeg" alt="Lugar Turistico" class="gallary-img">
            <a href="#" class="gallary-overlay" data-img="img-catamarca/Plaza_25_de_Mayo.jpeg">
                <i class="gallary-icon ti-plus"></i>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3 gallary-item wow fadeIn">
            <img src="img-catamarca/Mirador-Jumeal.jpg" alt="Lugar Turistico" class="gallary-img">
            <a href="#" class="gallary-overlay" data-img="img-catamarca/Mirador-Jumeal.jpg">
                <i class="gallary-icon ti-plus"></i>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3 gallary-item wow fadeIn">
            <img src="img-catamarca/dique-collagasta.jpg" alt="Lugar Turistico" class="gallary-img">
            <a href="#" class="gallary-overlay" data-img="img-catamarca/dique-collagasta.jpg">
                <i class="gallary-icon ti-plus"></i>
            </a>    
        </div>
        <div class="col-sm-6 col-lg-3 gallary-item wow fadeIn">
            <img src="img-catamarca/dunas-taton.jpg" alt="Lugar Turistico" class="gallary-img">
            <a href="#" class="gallary-overlay" data-img="img-catamarca/dunas-taton.jpg">
                <i class="gallary-icon ti-plus"></i>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3 gallary-item wow fadeIn">
            <img src="img-catamarca/ruta-adobe.jpg" alt="Lugar Turistico" class="gallary-img">
            <a href="#" class="gallary-overlay" data-img="img-catamarca/ruta-adobe.jpg">
                <i class="gallary-icon ti-plus"></i>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3 gallary-item wow fadeIn">
            <img src="img-catamarca/rodeo.jpg" alt="Lugar Turistico" class="gallary-img">
            <a href="#" class="gallary-overlay" data-img="img-catamarca/rodeo.jpg">
                <i class="gallary-icon ti-plus"></i>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3 gallary-item wow fadeIn">
            <img src="img-catamarca/Pirquitas.jpeg" alt="Lugar Turistico" class="gallary-img">
            <a href="#" class="gallary-overlay" data-img="img-catamarca/Pirquitas.jpeg">
                <i class="gallary-icon ti-plus"></i>
            </a>
        </div>
        <div class="col-sm-6 col-lg-3 gallary-item wow fadeIn">
            <img src="img-catamarca/cuesta-la-chilca.jpg" alt="Lugar Turistico" class="gallary-img">
            <a href="#" class="gallary-overlay" data-img="img-catamarca/cuesta-la-chilca.jpg">
                <i class="gallary-icon ti-plus"></i>
            </a>
        </div>
    </div>

    <!-- Destination Start -->
    <div id="destinos" class="container-fluid py-5">
        <div class="container pt-5 pb-3">
            <div class="text-center mb-3 pb-3">
                <h6 class="text-primary text-uppercase" style="letter-spacing: 5px;">Destinos</h6>
                <h1>Explorá los destinos más visitados</h1>
            </div>
            <div class="row">
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="destination-item position-relative overflow-hidden mb-2">
                        <img class="img-fluid" src="img-catamarca/mirador-cuesta-del-valle.jpg" alt="">
                        <a class="destination-overlay text-white text-decoration-none" href="http://localhost/catamap/pages/detalle-lugar.php?id=3">
                            <h5 class="text-white">Cuesta del Portezuelo</h5>
                            <span>Valle Viejo</span>
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="destination-item position-relative overflow-hidden mb-2">
                        <img class="img-fluid" src="img-catamarca/Plaza_25_de_Mayo.jpeg" alt="">
                        <a class="destination-overlay text-white text-decoration-none" href="http://localhost/catamap/pages/detalle-lugar.php?id=26">
                            <h5 class="text-white">Plaza 25 de Mayo</h5>
                            <span>Capital</span>
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="destination-item position-relative overflow-hidden mb-2">
                        <img class="img-fluid" src="img-catamarca/termas-de-fiambala.jpg" alt="">
                        <a class="destination-overlay text-white text-decoration-none" href="http://localhost/catamap/pages/detalle-lugar.php?id=9">
                            <h5 class="text-white">Termas de Fiambalá</h5>
                            <span>Tinogasta</span>
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="destination-item position-relative overflow-hidden mb-2">
                        <img class="img-fluid" src="img-catamarca/rodeo.jpg" alt="">
                        <a class="destination-overlay text-white text-decoration-none" href="http://localhost/catamap/pages/detalle-lugar.php?id=27">
                            <h5 class="text-white">El Rodeo</h5>
                            <span>Ambato</span>
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="destination-item position-relative overflow-hidden mb-2">
                        <img class="img-fluid" src="img-catamarca/cuesta-la-chilca.jpg" alt="">
                        <a class="destination-overlay text-white text-decoration-none" href="http://localhost/catamap/pages/detalle-lugar.php?id=36">
                            <h5 class="text-white">Cuesta La Chilca</h5>
                            <span>Andalgala</span>
                        </a>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="destination-item position-relative overflow-hidden mb-2">
                        <img class="img-fluid" src="img-catamarca/dique-el-jumeal.webp" alt="">
                        <a class="destination-overlay text-white text-decoration-none" href="http://localhost/catamap/pages/detalle-lugar.php?id=2">
                            <h5 class="text-white">Dique El Jumeal</h5>
                            <span>Capital</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Destination Start -->
    <!-- Service Start -->
    <div id="servicios" class="container-fluid py-5">
        <div class="container pt-5 pb-3">
            <div class="text-center mb-3 pb-3">
                <h6 class="text-primary text-uppercase" style="letter-spacing: 5px;">Servicios</h6>
                <h1>Todo lo que podés hacer en Catamap</h1>
            </div>
            <div class="row">

                <!-- Mapa -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-item bg-white text-center mb-2 py-5 px-4">
                        <i class="fa fa-2x fa-map-marked-alt mx-auto mb-4"></i>
                        <h5 class="mb-2">Explorá Catamarca</h5>
                        <p class="m-0">
                            Descubrí lugares únicos y poco conocidos a través de un mapa interactivo creado por la comunidad.
                        </p>
                    </div>
                </div>

                <!-- Red social -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-item bg-white text-center mb-2 py-5 px-4">
                        <i class="fa fa-2x fa-users mx-auto mb-4"></i>
                        <h5 class="mb-2">Conectá con Viajeros</h5>
                        <p class="m-0">
                            Seguidores, perfiles públicos y experiencias compartidas para inspirarte y planificar tu próxima aventura.
                        </p>
                    </div>
                </div>

                <!-- Opiniones -->
                <div class="col-lg-4 col-md-6 mb-4">
                    <div class="service-item bg-white text-center mb-2 py-5 px-4">
                        <i class="fa fa-2x fa-star mx-auto mb-4"></i>
                        <h5 class="mb-2">Opiná y Guardá Favoritos</h5>
                        <p class="m-0">
                            Calificá lugares, dejá reseñas y guardá tus favoritos para no perderte ningún destino.
                        </p>
                    </div>
                </div>

            </div>
        </div>
    </div>

    <!-- Service End -->
    <!-- REGISTRO / UNITE A CATAMAP -->
    <div class="container-fluid bg-registration py-5" style="margin: 90px 0;">
        <div class="container py-5">
            <div class="row align-items-center">

                <div class="col-lg-7 mb-5 mb-lg-0">
                    <div class="mb-4">
                        <h6 class="text-primary text-uppercase" style="letter-spacing: 5px;">Participá</h6>
                        <h1 class="text-white">Sumate a <span class="text-primary">Catamap</span></h1>
                    </div>
                    <p class="text-white">
                        Catamap es una plataforma colaborativa que conecta personas con los lugares más
                        auténticos de Catamarca. Registrate para descubrir nuevos destinos, compartir
                        experiencias, guardar favoritos y formar parte de una comunidad turística activa.
                    </p>
                    <ul class="list-inline text-white m-0">
                        <li class="py-2">
                            <i class="fa fa-check text-primary mr-3"></i>
                            Explorá y descubrí turismo alternativo
                        </li>
                        <li class="py-2">
                            <i class="fa fa-check text-primary mr-3"></i>
                            Compartí opiniones y sugerí nuevos lugares
                        </li>
                        <li class="py-2">
                            <i class="fa fa-check text-primary mr-3"></i>
                            Conectá con otros viajeros
                        </li>
                    </ul>
                </div>

                <div class="col-lg-5">
                    <div class="card border-0">
                        <div class="card-header bg-primary text-center p-4">
                            <h1 class="text-white m-0">Registrate Gratis</h1>
                        </div>
                        <div class="card-body rounded-bottom bg-white p-5 text-center">
                            <p class="mb-4">
                                Creá tu cuenta y empezá a explorar Catamarca de una forma diferente.
                            </p>
                            <a class="btn btn-primary btn-block py-3"
                            data-toggle="modal"
                            data-target="#loginModal">
                            Crear cuenta en Catamap
                            </a>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- FIN REGISTRO -->


    <!-- OPINIONES -->
    <div id="testimonios" class="container-fluid py-5">
        <div class="container py-5">
            <div class="text-center mb-3 pb-3">
            <h6 class="text-primary text-uppercase" style="letter-spacing: 5px;">Opiniones</h6>
            <h1>Qué dicen los exploradores de Catamap</h1>
            </div>
            <div class="owl-carousel testimonial-carousel">
            <div class="text-center pb-4">
                <img class="img-fluid mx-auto" src="img/testimonial-1.jpg" style="width: 100px; height: 100px;" >
                <div class="testimonial-text bg-white p-4 mt-n5">
                <p class="mt-5">“Gracias a Catamap conocimos lugares escondidos de la provincia que ni imaginábamos.”</p>
                <h5 class="text-truncate">Lucía Fernández</h5>
                <span>Turista</span>
                </div>
            </div>
            <div class="text-center">
                <img class="img-fluid mx-auto" src="img/testimonial-2.jpg" style="width: 100px; height: 100px;" >
                <div class="testimonial-text bg-white p-4 mt-n5">
                <p class="mt-5">“Excelente iniciativa para promover el turismo local y los emprendimientos catamarqueños.”</p>
                <h5 class="text-truncate">Carlos Herrera</h5>
                <span>Emprendedor</span>
                </div>
            </div>
            <div class="text-center">
                <img class="img-fluid mx-auto" src="img/testimonial-3.jpg" style="width: 100px; height: 100px;" >
                <div class="testimonial-text bg-white p-4 mt-n5">
                <p class="mt-5">“Lo usamos para planificar nuestras escapadas de fin de semana. Súper útil.”</p>
                <h5 class="text-truncate">Martina López</h5>
                <span>Viajera</span>
                </div>
            </div>
            <div class="text-center">
                <img class="img-fluid mx-auto" src="img/testimonial-4.jpg" style="width: 100px; height: 100px;" >
                <div class="testimonial-text bg-white p-4 mt-n5">
                <p class="mt-5">“Catamap une a todos los que amamos Catamarca. Una herramienta indispensable.”</p>
                <h5 class="text-truncate">Diego Morales</h5>
                <span>Guía Turístico</span>
                </div>
            </div>
            </div>
        </div>
    </div>
    <!-- FIN OPINIONES -->

    <!-- FOOTER -->
    <div class="container-fluid bg-dark text-white-50 py-5 px-sm-3 px-lg-5" style="margin-top: 90px;">
    <div class="row pt-5">
        <div class="col-lg-3 col-md-6 mb-5">
        <a href="#" class="navbar-brand">
            <h1 class="text-primary"><span class="text-white">CATA</span>MAP</h1>
        </a>
        <p>Catamap es una plataforma interactiva para descubrir, compartir y promover los lugares más
            hermosos de Catamarca.</p>
        <h6 class="text-white text-uppercase mt-4 mb-3" style="letter-spacing: 5px;">Seguinos</h6>
        <div class="d-flex justify-content-start">
            <a class="btn btn-outline-primary btn-square mr-2" href="#"><i class="fab fa-facebook-f"></i></a>
            <a class="btn btn-outline-primary btn-square mr-2" href="#"><i class="fab fa-instagram"></i></a>
            <a class="btn btn-outline-primary btn-square mr-2" href="#"><i class="fab fa-twitter"></i></a>
            <a class="btn btn-outline-primary btn-square" href="#"><i class="fab fa-youtube"></i></a>
        </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-5">
        <h5 class="text-white text-uppercase mb-4" style="letter-spacing: 5px;">Explorá</h5>
        <div class="d-flex flex-column justify-content-start">
            <a class="text-white-50 mb-2" href="#"><i class="fa fa-angle-right mr-2"></i>Inicio</a>
            <a class="text-white-50 mb-2" href="#"><i class="fa fa-angle-right mr-2"></i>Mapa</a>
            <a class="text-white-50 mb-2" href="#"><i class="fa fa-angle-right mr-2"></i>Destinos</a>
            <a class="text-white-50 mb-2" href="#"><i class="fa fa-angle-right mr-2"></i>Eventos</a>
            <a class="text-white-50" href="#"><i class="fa fa-angle-right mr-2"></i>Contacto</a>
        </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-5">
        <h5 class="text-white text-uppercase mb-4" style="letter-spacing: 5px;">Recursos</h5>
        <div class="d-flex flex-column justify-content-start">
            <a class="text-white-50 mb-2" href="#"><i class="fa fa-angle-right mr-2"></i>Preguntas Frecuentes</a>
            <a class="text-white-50 mb-2" href="#"><i class="fa fa-angle-right mr-2"></i>Guía del Viajero</a>
            <a class="text-white-50 mb-2" href="#"><i class="fa fa-angle-right mr-2"></i>Colaborar</a>
            <a class="text-white-50 mb-2" href="#"><i class="fa fa-angle-right mr-2"></i>Acerca de Catamap</a>
        </div>
        </div>

        <div class="col-lg-3 col-md-6 mb-5">
        <h5 class="text-white text-uppercase mb-4" style="letter-spacing: 5px;">Contacto</h5>
        <p><i class="fa fa-map-marker-alt mr-2"></i>Catamarca, Argentina</p>
        <p><i class="fa fa-phone-alt mr-2"></i>+54 383 4000000</p>
        <p><i class="fa fa-envelope mr-2"></i>info@catamap.com</p>
        <h6 class="text-white text-uppercase mt-4 mb-3" style="letter-spacing: 5px;">Suscribite</h6>
        <div class="w-100">
            <div class="input-group">
            <input type="text" class="form-control border-light" style="padding: 25px;" placeholder="Tu Email">
            <div class="input-group-append">
                <button class="btn btn-primary px-3">Enviar</button>
            </div>
            </div>
        </div>
        </div>
    </div>
    </div>

    <div class="container-fluid bg-dark text-white border-top py-4 px-sm-3 px-md-5" style="border-color: rgba(256, 256, 256, .1) !important;">
    <div class="row">
        <div class="col-lg-6 text-center text-md-left mb-3 mb-md-0">
        <p class="m-0 text-white-50">© 2025 <a href="#">Catamap</a>. Todos los derechos reservados.</p>
        </div>
        <div class="col-lg-6 text-center text-md-right">
        <p class="m-0 text-white-50">Diseñado por <a href="#">Catamap Team</a></p>
        </div>
    </div>
    </div>
    <!-- FOOTER END -->
    <!-- Modal de Inicio de Sesión / Registro -->
    <div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content rounded-4 shadow">
            <div class="modal-header border-0">
                <h5 class="modal-title" id="modalTitle">Inicia sesión en tu cuenta</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <!-- Formulario de Inicio de Sesión -->
                <form id="loginForm">
                    <div class="mb-3">
                        <label for="loginEmail" class="form-label">Correo electrónico</label>
                        <input type="email" class="form-control" id="loginEmail" placeholder="ejemplo@correo.com" required>
                    </div>
                    <div class="mb-3">
                        <label for="loginPassword" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="loginPassword" placeholder="********" required>
                            <div class="input-group-append">
                                <button class="btn btn-outline-white" type="button" id="toggleLoginPassword">
                                    <i class="bi bi-eye" id="toggleLoginPasswordIcon"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="captchaLogin" class="form-label">Verificación de seguridad</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text user-select-none" id="captchaQuestionLogin"></span>
                            </div>
                            <input type="number" class="form-control" id="captchaLogin" placeholder="Respuesta" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-primary w-100" id="btnLogin">Iniciar Sesión</button>
                </form>

                <!-- Separador -->
                <div class="text-center mt-3 mb-2 text-muted">o continúa con</div>
                    <!-- Botones sociales -->
                    <div class="text-center mb-2">
                        <span class="badge badge-info">Próximamente</span>
                    </div>
                    <div class="d-flex justify-content-center gap-2" style="opacity: 0.6;">
                        <button class="btn btn-outline-dark w-50 mr-2" disabled>
                            <i class="bi bi-google"></i> Google
                        </button>
                        <button class="btn btn-outline-dark w-50" disabled>
                            <i class="bi bi-facebook"></i> Facebook
                        </button>
                    </div>

                    <!-- Enlace para registrarse -->
                <div class="text-center mt-4">
                    ¿No tienes una cuenta? 
                    <a href="#" id="showRegister">Regístrate</a>
                </div>

                <!-- Formulario de Registro (oculto por defecto) -->
                <form id="registerForm" class="d-none mt-3">
                    <div class="mb-3">
                        <label for="registerName" class="form-label">Nombre completo</label>
                        <input type="text" class="form-control" id="registerName" placeholder="Tu nombre" required minlength="3">
                        <small class="form-text text-muted">Mínimo 3 caracteres</small>
                    </div>
                    <div class="mb-3">
                        <label for="registerEmail" class="form-label">Correo electrónico</label>
                        <input type="email" class="form-control" id="registerEmail" placeholder="ejemplo@correo.com" required>
                    </div>
                    <div class="mb-3">
                        <label for="registerPassword" class="form-label">Contraseña</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="registerPassword" placeholder="Crea una contraseña segura" required minlength="8">
                            <div class="input-group-append">
                                <button class="btn btn-outline-dark" type="button" id="toggleRegisterPassword">
                                    <i class="bi bi-eye" id="toggleRegisterPasswordIcon"></i>
                                </button>
                            </div>
                        </div>
                        <small class="form-text text-muted">
                            Mínimo 8 caracteres, incluir mayúsculas, minúsculas y números
                        </small>
                    </div>
                    <div class="mb-3">
                        <label for="registerConfirmPassword" class="form-label">Confirmar Contraseña</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="registerConfirmPassword" placeholder="Repite tu contraseña" required minlength="8">
                            <div class="input-group-append">
                                <button class="btn btn-outline-dark" type="button" id="toggleConfirmPassword">
                                    <i class="bi bi-eye" id="toggleConfirmPasswordIcon"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="captchaRegister" class="form-label">Verificación de seguridad</label>
                        <div class="input-group">
                            <div class="input-group-prepend">
                                <span class="input-group-text user-select-none" id="captchaQuestionRegister"></span>
                            </div>
                            <input type="number" class="form-control" id="captchaRegister" placeholder="Respuesta" required>
                        </div>
                    </div>
                    <button type="submit" class="btn btn-success w-100" id="btnRegister">Crear Cuenta</button>
                    <div class="text-center mt-3">
                        ¿Ya tienes una cuenta? <a href="#" id="showLogin">Inicia sesión</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
    <!-- Back to Top -->
    <a href="#" class="btn btn-lg btn-primary btn-lg-square back-to-top"><i class="fa fa-angle-double-up"></i></a>
    <!-- MODAL GALERÍA -->
<div class="modal fade" id="galleryModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg" role="document">
        <div class="modal-content bg-dark border-0">
            <div class="modal-body p-0 text-center">
                <button type="button" class="close text-white position-absolute" 
                        style="right: 15px; top: 10px;" data-dismiss="modal">
                    <span>&times;</span>
                </button>
                <img id="galleryModalImg" src="" class="img-fluid rounded" alt="Imagen ampliada">
            </div>
        </div>
    </div>
</div>
    <!-- end of page footer -->
    <!-- JavaScript Libraries -->
    <script src="https://code.jquery.com/jquery-3.4.1.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.4.1/js/bootstrap.bundle.min.js"></script>
    <script src="lib/easing/easing.min.js"></script>
    <script src="lib/owlcarousel/owl.carousel.min.js"></script>
    <script src="lib/tempusdominus/js/moment.min.js"></script>
    <script src="lib/tempusdominus/js/moment-timezone.min.js"></script>
    <script src="lib/tempusdominus/js/tempusdominus-bootstrap-4.min.js"></script>
    <!-- bootstrap affix -->
    <script src="assets/vendors/bootstrap/bootstrap.affix.js"></script>

    <!-- wow.js -->
    <script src="assets/vendors/wow/wow.js"></script>
    
    <!-- FoodHut js -->
    <script src="assets/js/foodhut.js"></script>
    <!-- Template Javascript -->
    <script src="js/main.js"></script>
    <script src="js/auth.js"></script>
    <script src="js/inicio-sesion.js?v=<?php echo time(); ?>"></script>
    <script>
    document.querySelectorAll('.gallary-overlay').forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();

            const imgSrc = this.getAttribute('data-img');
            const modalImg = document.getElementById('galleryModalImg');

            modalImg.src = imgSrc;

            $('#galleryModal').modal('show');
        });
    });
</script>
</body>
</html>
