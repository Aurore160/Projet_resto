<template>
  <div class="help-section">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-lg-6">
          <div class="help-content">
            <h6 class="logo"><Logo/></h6>
            <h1 class="help-main-title">Contactez-nous</h1>
            <h3 class="help-main-title1">ZEDUC Services</h3>
            
            
            <!-- Informations de contact -->
            <div class="contact-info">
              <div class="contact-item">
                <div class="contact-label">Email</div>
                <a class="contact-value" href="mailto:justicemukoka@gmail.com">
                  <span>Zeduc@gmail.com</span>
                </a>
              </div>
              <div class="contact-item">
                <div class="contact-label">Telephone</div>
                <a class="contact-value" href="https://wa.me/243995597143" target="_blank">
                  <span>243852137353</span>
                </a>
              </div>
            </div>

            <div class="input-group">
              <input type="text" class="form-control" placeholder="Ecrivez votre preoccupation">
              <button class="btn btn-send">Envoyer</button>
            </div>
          </div>
        </div>
        
        <!-- Première image -->
        <div class="col-lg-3">
          <div class="image-container">
            <img src="c:\Users\sanru\Downloads\Projet_resto-devellopement\Projet_resto-devellopement\src\images\1349b7c1821ac50e89a8d062702248c1.jpg"  class="help-image">
            <div class="image-overlay"></div>
          </div>
        </div>
        
        <!-- Deuxième image décalée encore plus bas -->
        <div class="col-lg-3 offset-image">
          <div class="image-container">
            <img src="c:\Users\sanru\Downloads\Projet_resto-devellopement\Projet_resto-devellopement\src\images\5e3275e7738b346c7750adcff5481698.jpg" class="help-image">
            <div class="image-overlay"></div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
// // export default {
// //   name: 'ChemicalHelpSection'
// }
</script>

<style scoped>
.logo{
    font-size: 40px;
}
.help-section {
  background: linear-gradient(135deg, var(--hover-color) 0%, var(--primary-color) 100%);
  color: var(--secondary-color);
  padding: 80px 0;
  min-height: 100vh;
  display: flex;
  align-items: center;
  font-family: 'Arial', sans-serif;
}
.col-lg-6{
  margin-top: 30px;
}

.help-content {
  max-width: 500px;
  margin-top: 30;
}

.help-mini-title {
  font-size: 14px;
  font-weight: 600;
  letter-spacing: 2px;
  margin-bottom: 20px;
  text-transform: uppercase;
  opacity: 0.8;
}

.help-main-title {
  font-size: 48px;
  font-weight: 700;
  line-height: 1.1;
  margin-bottom: 25px;
}
.help-main-title1 {
  font-size: 35px;
  font-weight: 700;
  line-height: 1.1;
  margin-bottom: 25px;
}

/* Styles pour les informations de contact */
.contact-info {
  margin-bottom: 55px;
  padding: 20px 0;
}

.contact-item {
  margin-bottom: 20px;
  padding-left: 0;
}

.contact-label {
  font-size: 14px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 1px;
  margin-bottom: 5px;
  opacity: 0.8;
}

.contact-value {
  font-size: 18px;
  font-weight: 500;
  color: var(--success-color);
  text-decoration: none;
}

.input-group {
  display: flex;
  max-width: 400px;
  background: var(--secondary-color);
  border-radius: 30px;
  overflow: hidden;
  box-shadow: 0 4px 15px rgba(0,0,0,0.1);
  margin-bottom: 1em;
}

.form-control {
  flex: 1;
  border: none;
  padding: 15px 25px;
  font-size: 16px;
  outline: none;
  background: transparent;
  color: var(--success-color);
}

.form-control::placeholder {
  color: var(--fin-color);
}

.btn-send {
  background: var(--primary-color);
  color: var(--secondary-color);
  border: none;
  padding: 15px 30px;
  font-size: 16px;
  font-weight: 600;
  cursor: pointer;
  transition:  0.3s ease;
  text-transform: uppercase;
}

.btn-send:hover {
  background: var(--hover-color);
}

/* Styles pour les images */
.image-container {
  position: relative;
  height: 500px;
  border-radius: 10px;
  overflow: hidden;
  box-shadow: 0 8px 25px rgba(0,0,0,0.2);
}

.help-image {
  width: 100%;
  height: 100%;
  object-fit: cover;
  transition: transform 4.3s ease;
}

.image-container:hover .help-image {
  transform: scale(1.05);
}

.image-overlay {
  position: absolute;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: linear-gradient(45deg, rgba(26, 58, 107, 0.3), rgba(42, 82, 152, 0.3));
  transition: 0.3s ease;
}

.image-container:hover .image-overlay {
  background: linear-gradient(45deg, rgba(26, 58, 107, 0.1), rgba(42, 82, 152, 0.1));
}

/* Style pour décaler la deuxième image encore plus bas */
.offset-image {
  margin-top: 110px; /* Augmenté de 100px à 180px */
}


/* Responsive Design */
@media (max-width: 992px) {
  .help-section {
    padding: 60px 0;
    text-align: center;
  }
  
  .help-main-title {
    font-size: 38px;
  }
  
  .help-content {
    margin: 0 auto;
    margin-bottom: 50px;
  }
  
  .input-group {
    margin: 0 auto;
  }
  
  .contact-info {
    text-align: left;
    padding-left: 20px;
  }
  
  /* Sur tablette, on réduit le décalage */
  .offset-image {
    margin-top: 80px;
  }

  .image-container {
    height: 400px;
  }
}

@media (max-width: 768px) {
  .help-main-title {
    font-size: 32px;
  }
  
  .help-description {
    font-size: 16px;
  }
  
  .input-group {
    flex-direction: column;
    border-radius: 15px;
  }
  
  .form-control {
    padding: 12px 20px;
  }
  
  .btn-send {
    padding: 12px 20px;
    border-radius: 0 0 15px 15px;
  }

  .contact-value {
    font-size: 16px;
  }
  
  /* Sur mobile, on réduit encore plus le décalage */
  .offset-image {
    margin-top: 40px;
  }

  .image-container {
    height: 300px;
  }
}

@media (max-width: 576px) {
  .help-main-title {
    font-size: 28px;
  }
  
  .contact-info {
    padding-left: 10px;
  }
  
  .contact-value {
    font-size: 14px;
  }
}
</style>