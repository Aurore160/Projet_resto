<template>
    <footer class="site-footer">
        <div class="col">
                <h4 class="col-title"> Zeduc-space </h4>
                <p class="col-desc"> Un bon plat, un service convivial - plats maison et produits frais. </p>
        </div>
        <div class="footer-inner">
            <div class="col">
                <h5 class="col-title"> Articles </h5>
                <ul class="social-list">
                    <li><a href="#" target="_blank" rel="noopener"><i class="fab fa-facebook-f"></i> Facebook </a></li>
                    <li><a href="#" target="_blank" rel="noopener"><i class="fab fa-twitter"></i> Twitter </a></li>
                    <li><a href="#" target="_blank" rel="noopener"><i class="fab fa-linkedin-in"></i> LinkedIn </a></li>
                    <li><a href="#" target="_blank" rel="noopener"><i class="fab fa-instagram"></i> Instagram </a></li>
                </ul>
            </div>

            <div class="col">
                <h5 class="col-title"> Navigation </h5>
                <ul class="nav-list">
                    <li><router-link to="/home"> Acceuil </router-link></li>
                    <li><router-link to="/menu"> Menu </router-link></li>
                    <li><router-link to="/about"> A propos </router-link></li>
                    <li><router-link to="/contact"> Contact </router-link></li>
                    <li><router-link to="/favorites">Favoris</router-link></li> 
                    <li><router-link to="/orders">Commandes</router-link></li>  
                </ul>
            </div>

            <div class="col">
                <h5 class="col-title"> Contact </h5>
                <ul class="contact-list">
                    <li><a :href="whatsappLink" target="_blank" rel="noopener"><i class="fab fa-whatsapp"></i> +243 825181727 </a></li>
                    <li><a href="mailto:rogermulumbu13@gmail.com"><i class="fas fa-envelope"></i> zeduc@gmail.com </a></li>
                </ul>
            </div>

            <div class="col">
                <h5 class="col-title"> Adresse </h5>
                <p> Wenge Q/Righini, C/Lemba, Kinshasa, RDC </p>
            </div>

            <div class="col">
                <h5 class="col-title"> Horaires </h5>
                <p> Lundi-Vendredi 8h-21h <br> Samedi 9h-00h <br> Dimanche 9h-23h </p>
            </div>
        </div>
        <div class="copyright"> &copy; copyright {{ year }} -Tous droits réservés </div>
    </footer>
</template>

<script setup>
import { ref } from "vue";
const year = new Date() .getFullYear();
const phone = "+243825181727";
const whatsappLink = `https://wa.me/${phone.replace(/\D/g,'')}`;
</script>

<style scoped>
.site-footer{
    background: #cfbd97;
    color:#111;
    padding: 36px 16px 12px;
}

.footer-inner{
    max-width:1200px;
    margin: 0 auto;
    display: flex;
    gap: 28px; 
    flex-wrap: wrap;
    align-items: flex-start;
}

.col{
    flex:1 1 180px;
    min-width: 160px;
}

.col-title { 
    font-weight: 700; 
    margin-bottom: 8px;
}
.col-desc { 
    color: #3a3a3a; 
    margin-bottom: 12px; 
}

.footer-inner .social-list, .footer-inner .nav-list, .footer-inner .contact-list {
    list-style:none;
    padding:0;
    margin:0;
}

.footer-inner a{ 
    color:#111; 
    text-decoration: none; 
    display: block; 
    margin-bottom:8px; 
}

.footer-inner i{
    margin-right:8px;
}

@media (max-width: 900px) {
    .footer-inner {
        gap: 18px;
    }
}

.copyright{
    text-align:center;
    margin-top:18px;
    font-size:14px;
    color:#222;
}
</style>