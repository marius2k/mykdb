<?php

require_once '../config/bootstrap.php';

require_login();

$ops=['view_own_logs','view_all_logs'];

if (!hasPermission($_SESSION['user']['id'],$ops)) {
    
    $_SESSION['flash'] = "⚠️ Access Denied";
    $referer = $_SERVER['HTTP_REFERER'] ?? '/mykdb/public/index.php';

    echo "<script>
            alert('⚠️ Access Denied');
            window.location.href = '$referer';
        </script>";
    exit;     
}

$db = new Database();

$userId = $_SESSION['user']['id'];

/*
$drafts = $db->fetchAll("
  SELECT id, title, created_at 
  FROM articles 
  WHERE user_id = ? AND status = 'draft'
  ORDER BY created_at DESC
", [$userId]);
*/


$drafts=getDraftsArticles($userId);


// Fetch notifications for current user
/*$notifications = $db->fetchAll("
    SELECT id, title, message, type, is_read, created_at
    FROM notifications
    WHERE user_id = ? AND is_read=0
    ORDER BY created_at DESC
", [$userId]);
*/

$notifications = getUnreadNotifications($userId);

$days=20;

$articlesStats = getArticlesByLastDays($days);
$chartLabels = json_encode(array_keys($articlesStats));
$chartData = json_encode(array_values($articlesStats));
$totalArticles = array_sum($articlesStats);


//debuging
/*
foreach ($articlesStats as $day => $count) {
    echo "Day: $day, Count: $count<br>";
}
*/


$commentsStats = getCommentsByLastDays($days);
$chartLabelsComments = json_encode(array_keys($commentsStats));
$chartDataComments = json_encode(array_values($commentsStats));
$totalComments = array_sum($commentsStats);

// Mark all as read (optional)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_all_read'])) {
    $db->query("UPDATE notifications SET is_read = 1 WHERE user_id = ?", [$userId]);
    header("Location: notifications.php");
    exit;
}



?>

<?php include APP_ROOT . 'includes/header.php'; ?>



<div class="dashboard-wrapper">

  <!-- 🧩 Coloana stânga -->
  <div class="dashboard-left">

        <!-- Drafts -->
 
    <?php
      echo renderDrafts(($drafts));
    ?>

    <!-- Notifications -->
    <?php
      
      echo renderNotifications(($notifications));

    ?>

  </div>




  <!-- 🧠 Coloana dreapta -->
  <div class="dashboard-right">

    <div class="custom-box-1">
      <?php
            $artViewDays = 30;
        ?>
        <span class="corner-label-1"><?php echo lang_db_top5_views . $artViewDays . lang_db_art_days; ?></span>
        <div class="box-content-1" style="padding-top: 20px; gap: 0px;">
                    <?php echo getTopViewedArticles(5); ?>
        </div>
    </div>
    <div class="custom-box-1">
      <?php
            $artLikeDays = 30;
        ?>
        <span class="corner-label-1"><?php echo lang_db_top5_likes . $artLikeDays . lang_db_art_days; ?></span>
        <div class="box-content-1" style="padding-top: 20px; gap: 0px;">
            <?php echo getTop5LikedArticles($artLikeDays); ?>
        </div>
    </div>
    <div class="custom-box-1">
        <?php
            $artCommDays = 30;
        ?>
         <span class="corner-label-1"><?php echo lang_db_top5_commented . $artCommDays . lang_db_art_days; ?></span>
        <div class="box-content-1" style="padding-top: 20px; gap: 0px;">
            <?php
                
                echo getTop5CommentedArticles($artCommDays); 
                
            ?>
        </div>
    </div>
    <div class="custom-box-1" style="padding: 10px; gap: 0px;">
        <span class="corner-label-1"><?=lang_db_articles?></span>
        <div class="box-content-1" style = "padding: 10px; width:100%; max-width;400px; height:auto; position:relative; margin: 0 auto;">
            <canvas id="articlesChart" height="130"></canvas>
        </div>
        
        
    </div>
    <div class="custom-box-1">  
        <span class="corner-label-1"><?=lang_db_recent_comments?></span>
        <div class="box-content-1" style = "padding:10px; width:100%; max-width;400px; height:auto; position:relative; margin: 0 auto;">
            <canvas id="commentsChart" height="130"></canvas>
        </div>
    </div>
    
    <div class="custom-box-1">
        <span class="corner-label-1">Operatiuni</span>
        <ul>
          <li>18 create</li>
          <li>7 editate</li>
          <li>2 șterse</li>
        </ul>
    </div>
    <div class="custom-box-1">
        <span class="corner-label-1">Loguri</span>
        <ul>
          <li>104 azi</li>
          <li>7 arhivate</li>
          <li>3 șterse</li>
        </ul>
    </div>
    <div class="custom-box-1">
        <span class="corner-label-1">Exporturi</span>
        <ul>
          <li>1 CSV azi</li>
          <li>3 backup-uri</li>
          <li>Ultimul: 2024-05-01</li>
        </ul>
    </div>

  </div>

</div>




<?php include APP_ROOT . 'includes/footer.php'; ?>

<script>

const ctx1 = document.getElementById('articlesChart').getContext('2d');
const dataValues1 = <?= $chartData ?>;
const dataLabels1 = <?= $chartLabels ?>;
const maxValue1 = Math.max(...dataValues1) + 1; // Adaugă 10 la valoarea maximă

new Chart(ctx1, {
    type: 'bar',
    data: {
        labels: dataLabels1,
        datasets: [{
            label: '<?=lang_db_articles_published?>',
            data: dataValues1,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1,
            borderRadius: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            title: {
                display: true,
                text: '<?php echo lang_db_art_published_last_days.$days.lang_db_art_days ?> (<?=$totalArticles?>)'
            }
        },
        scales: {
            x: {
                ticks: {
                    display: false
                }
            },
            y: {
                ticks: {
                    font: {
                        size: 10,
                        
                    },
                    stepSize: 1 // Setează pasul pentru unitățile de pe axa Y
                },
                max: maxValue1, // Setează valoarea maximă
                beginAtZero: true,
                precision: 0
            }
        }
    }
});

const ctx2 = document.getElementById('commentsChart').getContext('2d');
const dataValues2 = <?= $chartDataComments ?>;
const dataLabels2 = <?= $chartLabelsComments ?>;
const maxValue2 = Math.max(...dataValues2) + 1; // Adaugă 10 la valoarea maximă



new Chart(ctx2, {
    type: 'bar',
    data: {
        labels: dataLabels2,
        datasets: [{
            label: '<?= lang_db_comments_received ?>',
            data: dataValues2,
            backgroundColor: 'rgba(54, 162, 235, 0.6)',
            borderColor: 'rgba(54, 162, 235, 1)',
            borderWidth: 1,
            borderRadius: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: { display: false },
            title: {
                display: true,
                text: '<?php echo lang_db_comments_last_days.$days.lang_db_art_days ?> (<?=$totalComments?>)'
            }
        },
        scales: {
            x: {
                ticks: {
                    display: false
                }
            },
            y: {
                max: maxValue2, // Setează valoarea maximă
                beginAtZero: true,
                precision: 0,
                ticks: {
                    font: {
                        size: 10
                    },
                    stepSize: 1 // Setează pasul pentru unitățile de pe axa Y
                }
                
            }
        }
    }
});

function updateNotifBadge(count) {
  const badge = document.getElementById('notif-badge');
  if (badge) {
    if (count > 0) {
      badge.textContent = count;
      badge.style.display = 'inline-block';
    } else {
      badge.textContent = '';
      badge.style.display = 'none';
    }
  }
}


function markRead(id) {
  fetch('update_notification.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: `action=mark_read&id=${encodeURIComponent(id)}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'ok') {
      const row = document.getElementById(`notif-${id}`);
      if (row) row.remove();
      updateNotifBadge(data.remaining);  // 👈 actualizează badge-ul
    } else {
      alert('Eroare la marcare notificare ca citită');
    }
  })
  .catch(err => {
    console.error(err);
    alert('Eroare AJAX la markRead');
  });
}



function deleteNotif(id) {
  if (!confirm('⚠️ Sigur vrei să ștergi această notificare?')) return;

  fetch('update_notification.php', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/x-www-form-urlencoded'
    },
    body: `action=delete&id=${encodeURIComponent(id)}`
  })
  .then(res => res.json())
  .then(data => {
    if (data.status === 'ok') {
      const row = document.getElementById(`notif-${id}`);
      if (row) row.remove();
      updateNotifBadge(data.remaining);  // 👈 actualizează badge-ul
    } else {
      alert('Eroare la ștergere notificare');
    }
  })
  .catch(err => {
    console.error(err);
    alert('Eroare AJAX la deleteNotif');
  });
}




 // Când DOM-ul este gata, inițializăm toate box-urile
  document.addEventListener('DOMContentLoaded', () => {
    const allCustomBoxes = document.querySelectorAll('.custom-box-1');
    allCustomBoxes.forEach(box => {
      initializeCustomBox1(box);
    });

    // Exemplu de inițializare cu o culoare de fundal specifică, dacă ar fi cazul
    // const specificBox = document.querySelector('.another-box-style');
    // if (specificBox) {
    //   initializeCustomBox(specificBox, 'lightgray'); // Dacă acest box e pe un fundal gri
    // }
  });

  </script>