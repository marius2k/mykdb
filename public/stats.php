<?php include '../config/bootstrap.php'; ?>


<?php include APP_ROOT . 'includes/header.php'; ?>
<h4 style="margin-bottom:20px;">📊 <?= lang_db_analytics ?? 'Analytics Dashboard' ?></h4>

<!-- GRID CU 3 COLOANE -->
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); ">

  <!-- 🔹 Articole vizualizate -->
  <div class="custom-box-1">
    <span class="corner-label-1">Top 5 Vizualizări</span>
    <div class="box-content-1" style="paddig:20px;">
      <ul>
        <li>Articol 1 - 1230 views</li>
        <li>Articol 2 - 980 views</li>
        <li>Articol 3 - 850 views</li>
        <li>Articol 4 - 640 views</li>
        <li>Articol 5 - 530 views</li>
      </ul>
    </div>
  </div>

  <!-- 🔹 Articole cu like-uri -->
  <div class="custom-box-1">
    <span class="corner-label-1">Top 5 Like-uri</span>
    <div class="box-content-1">
      <ul>
        <li>Articol A - 150 👍</li>
        <li>Articol B - 130 👍</li>
        <li>Articol C - 120 👍</li>
        <li>Articol D - 110 👍</li>
        <li>Articol E - 90 👍</li>
      </ul>
    </div>
  </div>

  <!-- 🔹 Activitate comentarii -->
  <div class="custom-box-1">
    <span class="corner-label-1">Comentarii Recente</span>
    <div class="box-content-1">
      <ul>
        <li>10 comentarii în ultimele 24h</li>
        <li>45 comentarii în ultimele 7 zile</li>
        <li>120 comentarii în ultima lună</li>
      </ul>
    </div>
  </div>

  <!-- 🔹 Utilizatori activi -->
  <div class="custom-box-1">
    <span class="corner-label-1">Utilizatori Activi</span>
    <div class="box-content-1">
      <ul>
        <li>5 utilizatori activi azi</li>
        <li>12 logări azi</li>
        <li>3 utilizatori inactivi 30+ zile</li>
      </ul>
    </div>
  </div>

  <!-- 🔹 Număr articole -->
  <div class="custom-box-1">
    <span class="corner-label-1">Articole Publicate</span>
    <div class="box-content-1">
      <ul>
        <li>7 articole noi luna asta</li>
        <li>53 în total</li>
        <li>12 draft-uri</li>
      </ul>
    </div>
  </div>

  <!-- 🔹 Notificări -->
  <div class="custom-box-1">
    <span class="corner-label-1">Notificări</span>
    <div class="box-content-1">
      <ul>
        <li>2 necitite</li>
        <li>5 trimise azi</li>
        <li>32 total luna curentă</li>
      </ul>
    </div>
  </div>

  <!-- 🔹 Operatii CRUD -->
  <div class="custom-box-1">
    <span class="corner-label-1">Operațiuni Sistem</span>
    <div class="box-content-1">
      <ul>
        <li>18 articole create</li>
        <li>7 articole editate</li>
        <li>2 articole șterse</li>
      </ul>
    </div>
  </div>

  <!-- 🔹 Loguri -->
  <div class="custom-box-1">
    <span class="corner-label-1">Loguri Activitate</span>
    <div class="box-content-1">
      <ul>
        <li>104 loguri azi</li>
        <li>7 arhivate</li>
        <li>3 șterse</li>
      </ul>
    </div>
  </div>

  <!-- 🔹 Exporturi -->
  <div class="custom-box-1">
    <span class="corner-label-1">Exporturi & Backup</span>
    <div class="box-content-1">
      <ul>
        <li>1 CSV exportat azi</li>
        <li>3 backup-uri efectuate</li>
        <li>Ultimul backup: 2024-05-01</li>
      </ul>
    </div>
  </div>

</div>

<?php include APP_ROOT . 'includes/footer.php'; ?>
<script>

    document.addEventListener('DOMContentLoaded', () => {
    const allCustomBoxes = document.querySelectorAll('.custom-box-1');
    allCustomBoxes.forEach(box => {
      initializeCustomBox1(box);
    });
    })
</script>