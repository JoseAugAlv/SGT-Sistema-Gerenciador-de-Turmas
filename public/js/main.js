// ============================================================
// PROJETOBASE - MAIN.JS
// Menu Hamburger, Animacoes e Utilitarios gerais
// (Logica de dropdown/mega-menu mora em nav.js)
// ============================================================

document.addEventListener('DOMContentLoaded', function() {

  // ==========================================
  // MENU HAMBURGER
  // ==========================================
  const openBtn = document.getElementById('openBtn');
  const closeBtn = document.getElementById('closeBtn');
  const sidebar = document.getElementById('sidebar');
  const body = document.body;

  if (openBtn && sidebar) {
    openBtn.addEventListener('click', function(e) {
      e.stopPropagation();
      sidebar.classList.add('active');
      body.style.overflow = 'hidden';
    });
  }

  if (closeBtn && sidebar) {
    closeBtn.addEventListener('click', function() {
      sidebar.classList.remove('active');
      body.style.overflow = '';
    });
  }

  if (sidebar) {
    sidebar.querySelectorAll('a').forEach(function(link) {
      link.addEventListener('click', function() {
        sidebar.classList.remove('active');
        body.style.overflow = '';
      });
    });
  }

  document.addEventListener('click', function(e) {
    if (sidebar && sidebar.classList.contains('active')) {
      if (!sidebar.contains(e.target) && openBtn && !openBtn.contains(e.target)) {
        sidebar.classList.remove('active');
        body.style.overflow = '';
      }
    }
  });

  // ==========================================
  // SCROLL SUAVE PARA ANCORAS
  // ==========================================
  document.querySelectorAll('a[href^="#"]').forEach(function(anchor) {
    anchor.addEventListener('click', function(e) {
      var target = document.querySelector(this.getAttribute('href'));
      if (target) {
        e.preventDefault();
        target.scrollIntoView({ behavior: 'smooth' });
      }
    });
  });

  // ==========================================
  // ANIMACAO DE ENTRADA AO SCROLL
  // ==========================================
  const animateElements = document.querySelectorAll('.animate-in');

  const observer = new IntersectionObserver(function(entries) {
    entries.forEach(function(entry) {
      if (entry.isIntersecting) {
        entry.target.classList.add('visible');
      }
    });
  }, {
    threshold: 0.1
  });

  animateElements.forEach(function(el) {
    observer.observe(el);
  });

  // ==========================================
  // AUTO-FECHAR FLASH MESSAGES
  // ==========================================
  document.querySelectorAll('.flash-sucesso, .flash-erro, .flash-aviso').forEach(function(msg) {
    setTimeout(function() {
      msg.style.transition = 'opacity 0.5s ease';
      msg.style.opacity = '0';
      setTimeout(function() {
        msg.style.display = 'none';
      }, 500);
    }, 4000);
  });

  console.log('ProjetoBase - main.js inicializado com sucesso!');
});