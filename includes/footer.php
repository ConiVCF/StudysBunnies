<?php
$footerModo = $footerModo ?? 'normal';
?>

<footer class="site-footer site-footer--<?php echo htmlspecialchars($footerModo, ENT_QUOTES, 'UTF-8'); ?>">
    <div class="container site-footer__grid">
        <section class="site-footer__section">
            <h2>Study's Bunnies</h2>
            <p>Study's Bunnies combina estudio, misiones academicas, Pomodoro y acompanamiento con IA en una sala pixel-art.</p>
        </section>

        <section class="site-footer__section">
            <h2>Contacto</h2>
            <p>cozystudy@bunnyworld.com</p>
            <p>Buenos Aires, Argentina</p>
        </section>

        <section class="site-footer__section">
            <h2>Soporte</h2>
            <a href="contacto.php">Formulario de ayuda</a>
            <a href="index.php?menu=1#quienes-somos">Quienes somos</a>
        </section>

        <section class="site-footer__section">
            <h2>Creditos</h2>
            <p>Proyecto academico de Desarrollo Web.</p>
            <div class="site-footer__social">
                <span>UTN</span>
                <span>Haedo</span>
                <span>Programacion</span>
            </div>
        </section>
    </div>

    <div class="container site-footer__bottom">
        <span>&copy; <?php echo date('Y'); ?> Study's Bunnies</span>
        <span>Hecho para aprender mejor, una mision a la vez.</span>
    </div>
</footer>
