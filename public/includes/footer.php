<!-- Footer -->
<footer>
    <div class="container">
        <div class="row">
            <div class="col-md-5 mb-4">
                <h5 class="footer-title"><i class="fas fa-hotel me-2"></i>Hôtel Neptune</h5>
                <p class="mb-0">Confort, elegance et service premium pour un sejour inoubliable.</p>
            </div>
            <div class="col-md-4 mb-4">
                <h5 class="footer-title">Navigation</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="index.php" class="footer-link"><i class="fas fa-angle-right me-2"></i>Accueil</a></li>
                    <li class="mb-2"><a href="chambres.php" class="footer-link"><i class="fas fa-angle-right me-2"></i>Chambres</a></li>
                    <li class="mb-2"><a href="reservation.php" class="footer-link"><i class="fas fa-angle-right me-2"></i>Reservation</a></li>
                    <li class="mb-2"><a href="contact.php" class="footer-link"><i class="fas fa-angle-right me-2"></i>Contact</a></li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <li><a href="messages.php" class="footer-link"><i class="fas fa-angle-right me-2"></i>Mes messages</a></li>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="col-md-4">
                <h5 class="footer-title">Contact</h5>
                <p class="mb-2"><i class="fas fa-map-marker-alt me-2"></i>Montpellier, France 34000</p>
                <p class="mb-2"><i class="fas fa-phone-alt me-2"></i>0695396132</p>
                <p class="mb-2"><i class="fas fa-envelope me-2"></i>fayed.amourani8@gmail.com</p>
            </div>
        </div>
    </div>
    <div class="py-3" style="border-top:1px solid rgba(255,255,255,0.12);">
        <div class="container text-center">
            <p class="m-0">&copy; <?php echo date('Y'); ?> Hôtel Neptune. Tous droits réservés.</p>
        </div>
    </div>
</footer>

<!-- Bouton retour en haut de page -->
<a id="back-to-top" href="#" class="btn btn-primary btn-lg back-to-top" role="button">
    <i class="fas fa-chevron-up"></i>
</a>

<style>
    .back-to-top {
        position: fixed;
        bottom: 25px;
        right: 25px;
        display: none;
        width: 50px;
        height: 50px;
        text-align: center;
        line-height: 50px;
        border-radius: 50%;
        padding: 0;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
        z-index: 9999;
        transition: all 0.3s ease;
    }
    
    .back-to-top:hover {
        background-color: var(--primary-color);
        transform: translateY(-3px);
    }
    
    .back-to-top i {
        line-height: 50px;
    }
</style>

<!-- JavaScript dependencies -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/aos/2.3.4/aos.js"></script>
<script>
    // Initialize AOS animation library
    AOS.init({
        duration: 800,
        once: true
    });
    
    // Change navbar background on scroll
    window.addEventListener('scroll', function() {
        const navbar = document.querySelector('.navbar');
        if (window.scrollY > 50) {
            navbar.classList.add('scrolled');
        } else {
            navbar.classList.remove('scrolled');
        }
        
        // Show/hide back to top button
        const backToTopButton = document.getElementById('back-to-top');
        if (window.scrollY > 300) {
            backToTopButton.style.display = 'block';
        } else {
            backToTopButton.style.display = 'none';
        }
    });
    
    // Back to top functionality
    document.getElementById('back-to-top').addEventListener('click', function(e) {
        e.preventDefault();
        window.scrollTo({top: 0, behavior: 'smooth'});
    });
</script> 
</body>
</html>