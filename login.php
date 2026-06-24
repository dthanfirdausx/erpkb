<?php 
session_start();
include 'inc/config.php';

if (empty($_SESSION['login_csrf_token'])) {
  $_SESSION['login_csrf_token'] = bin2hex(random_bytes(32));
}

if (!isset($_SESSION['login'])) {
$loginLang = erp_lang_bundle(array(
  'login_panel_label' => 'Application login',
  'login_hero_text' => 'Bonded Zone ERP portal to manage inventory, production, sales, customs documents, and finance in one workflow.',
  'login_secure_access' => 'Secure ERP Access',
  'login_welcome_back' => 'Welcome Back',
  'login_title' => 'Sign in to ERPKB',
  'login_subtitle' => 'Use your registered account to continue today\'s operational work.',
  'login_failed_title' => 'Login failed.',
  'login_invalid_default' => 'Username or password does not match',
  'login_username' => 'Username',
  'login_password' => 'Password',
  'login_username_placeholder' => 'Enter username',
  'login_password_placeholder' => 'Enter password',
  'login_submit' => 'Sign in',
  'login_processing' => 'Processing...',
  'login_required' => 'Username and password are required.',
  'login_server_unresponsive' => 'Login server did not respond. Please try again shortly.',
  'login_developed_by' => 'Developed by'
));
?>
<!DOCTYPE html>
<html lang="<?=htmlspecialchars(isset($_SESSION['language']) ? $_SESSION['language'] : 'en', ENT_QUOTES, 'UTF-8');?>">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no" />
    <meta http-equiv="x-ua-compatible" content="ie=edge" />
    <title><?= appTittle ?></title>
    <link rel="icon" href="assets/login/img/favicon.png?v=20260620" type="image/png" />
    <link rel="shortcut icon" href="assets/login/img/favicon.png?v=20260620" type="image/png" />
    <link rel="apple-touch-icon" href="assets/login/img/favicon.png?v=20260620" />
    <link rel="stylesheet" href="mdb/css/mdb.min.css" />
    <style type="text/css">
      :root {
        --brand-primary: #0f766e;
        --brand-primary-dark: #115e59;
        --brand-accent: #f59e0b;
        --surface: #ffffff;
        --ink: #0f172a;
        --muted: #64748b;
        --line: #e2e8f0;
      }

      * {
        box-sizing: border-box;
      }

      body {
        min-height: 100vh;
        margin: 0;
        color: var(--ink);
        font-family: Inter, "Segoe UI", Arial, sans-serif;
        background:
          radial-gradient(circle at top left, rgba(20, 184, 166, .18), transparent 34rem),
          linear-gradient(135deg, #f8fafc 0%, #eef7f6 45%, #f6f8fb 100%);
      }

	      .login-shell {
	        min-height: 100vh;
	        display: flex;
	        align-items: center;
	        justify-content: center;
	        padding: 28px 18px;
	      }

	      .login-panel {
	        width: min(1060px, 100%);
	        display: grid;
	        grid-template-columns: 1.05fr .95fr;
	        overflow: hidden;
        border: 1px solid rgba(226, 232, 240, .9);
        border-radius: 28px;
        background: rgba(255, 255, 255, .86);
        box-shadow: 0 30px 80px rgba(15, 23, 42, .13);
        backdrop-filter: blur(16px);
      }

	      .login-hero {
        position: relative;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
	        min-height: 560px;
	        padding: 42px;
        overflow: hidden;
        color: #ffffff;
        background:
          linear-gradient(145deg, rgba(15, 118, 110, .95), rgba(15, 82, 96, .96)),
          url("assets/login/img/bg_new5.jpg") center/cover no-repeat;
      }

      .login-hero:before {
        content: "";
        position: absolute;
        inset: 0;
        background:
          radial-gradient(circle at 80% 18%, rgba(245, 158, 11, .26), transparent 16rem),
          linear-gradient(180deg, rgba(15, 23, 42, .06), rgba(15, 23, 42, .28));
      }

      .hero-content,
      .hero-footer {
        position: relative;
        z-index: 1;
      }

      .brand-mark {
        width: 78px;
        height: 78px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-bottom: 28px;
        border-radius: 22px;
        background: rgba(255, 255, 255, .92);
        box-shadow: 0 14px 34px rgba(15, 23, 42, .18);
      }

      .brand-mark img {
        max-width: 58px;
        max-height: 58px;
        object-fit: contain;
      }

      .hero-title {
        max-width: 470px;
        margin: 0;
        font-size: clamp(2rem, 4vw, 3.2rem);
        line-height: 1.08;
        font-weight: 800;
        letter-spacing: -.04em;
      }

      .hero-text {
        max-width: 440px;
        margin: 20px 0 0;
        color: rgba(255, 255, 255, .82);
        font-size: 1rem;
        line-height: 1.7;
      }

      .hero-pills {
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        margin-top: 30px;
      }

      .hero-pill {
        padding: 9px 13px;
        border: 1px solid rgba(255, 255, 255, .24);
        border-radius: 999px;
        background: rgba(255, 255, 255, .12);
        color: rgba(255, 255, 255, .92);
        font-size: .82rem;
        font-weight: 600;
      }

      .hero-footer {
        color: rgba(255, 255, 255, .72);
        font-size: .88rem;
      }

	      .login-card-wrap {
	        display: flex;
	        align-items: center;
	        padding: 44px 48px;
	        background: rgba(255, 255, 255, .94);
	      }

	      .login-card {
	        width: 100%;
	        max-width: 430px;
	        margin: 0 auto;
	      }

	      .login-form {
	        width: 100%;
	        margin: 0;
	      }

      .mobile-logo {
        display: none;
        width: 66px;
        height: 66px;
        align-items: center;
        justify-content: center;
        margin: 0 auto 18px;
        border-radius: 20px;
        background: #f8fafc;
        border: 1px solid var(--line);
      }

      .mobile-logo img {
        max-width: 48px;
        max-height: 48px;
        object-fit: contain;
      }

      .login-eyebrow {
        margin: 0 0 8px;
        color: var(--brand-primary);
        font-size: .82rem;
        font-weight: 800;
        letter-spacing: .11em;
        text-transform: uppercase;
      }

	      .login-language {
	        display: flex;
	        justify-content: flex-end;
	        margin-bottom: 20px;
	      }

	      .login-language form {
	        margin: 0;
	      }

      .login-language select {
        min-height: 38px;
        border: 1px solid var(--line);
        border-radius: 999px;
        background: #f8fafc;
        color: #334155;
        padding: 7px 14px;
        font-size: .86rem;
        font-weight: 700;
      }

	      .login-title {
	        margin: 0;
	        color: var(--ink);
	        font-size: 1.95rem;
	        font-weight: 800;
	        letter-spacing: -.035em;
	      }

	      .login-subtitle {
	        margin: 10px 0 26px;
	        color: var(--muted);
	        line-height: 1.65;
	      }

      .login-alert {
        border: 0;
        border-radius: 16px;
        background: #fef2f2;
        color: #991b1b;
        padding: 13px 15px;
        font-size: .92rem;
      }

	      .field-group {
	        margin-bottom: 16px;
	      }

      .field-label {
        display: block;
        margin-bottom: 8px;
        color: #334155;
        font-size: .9rem;
        font-weight: 700;
      }

	      .login-input {
        width: 100%;
	        min-height: 50px;
        border: 1px solid var(--line);
        border-radius: 16px;
        background: #f8fafc;
        color: var(--ink);
        font-size: .98rem;
        padding: 14px 16px;
        transition: border-color .2s ease, box-shadow .2s ease, background .2s ease;
      }

      .login-input:focus {
        outline: none;
        border-color: rgba(15, 118, 110, .58);
        background: #ffffff;
        box-shadow: 0 0 0 4px rgba(20, 184, 166, .12);
      }

	      .login-actions {
	        margin-top: 24px;
	      }

	      .login-button {
	        width: 100%;
	        min-height: 50px;
        border: 0;
        border-radius: 16px;
        background: linear-gradient(135deg, var(--brand-primary), var(--brand-primary-dark));
        color: #ffffff;
        font-weight: 800;
        letter-spacing: .01em;
        box-shadow: 0 14px 28px rgba(15, 118, 110, .25);
      }

      .login-button:hover,
      .login-button:focus {
        color: #ffffff;
        filter: brightness(.98);
        box-shadow: 0 18px 34px rgba(15, 118, 110, .32);
      }

      .login-button.loading {
        background: #94a3b8;
        box-shadow: none;
      }

	      .login-footnote {
	        margin: 22px 0 0;
        color: #94a3b8;
        font-size: .83rem;
        line-height: 1.6;
        text-align: center;
      }

      .login-footnote a {
        color: var(--brand-primary);
        font-weight: 700;
      }

      @media (max-width: 900px) {
        .login-shell {
          align-items: flex-start;
          padding: 22px 14px;
        }

        .login-panel {
          grid-template-columns: 1fr;
          border-radius: 24px;
        }

        .login-hero {
          display: none;
        }

	        .login-card-wrap {
	          padding: 34px 22px;
	        }

        .mobile-logo {
          display: flex;
        }

	        .login-card {
	          max-width: 480px;
	          text-align: center;
	        }

	        .login-language {
	          justify-content: center;
	        }

        .field-group,
        .login-alert {
          text-align: left;
        }
      }

      @media (max-width: 420px) {
        .login-shell {
          padding: 0;
        }

        .login-panel {
          min-height: 100vh;
          border: 0;
          border-radius: 0;
        }

        .login-card-wrap {
          padding: 30px 18px;
        }

        .login-title {
          font-size: 1.65rem;
        }
      }
    </style>
  </head>
  <body>
    <main class="login-shell">
      <section class="login-panel" aria-label="<?=htmlspecialchars($loginLang['login_panel_label'], ENT_QUOTES, 'UTF-8');?>">
        <aside class="login-hero">
          <div class="hero-content">
            <div class="brand-mark">
              <img src="assets/logo_kb3.png" alt="<?=htmlspecialchars(namaPT, ENT_QUOTES, 'UTF-8');?>">
            </div>
            <h1 class="hero-title"><?=htmlspecialchars(appTittle, ENT_QUOTES, 'UTF-8');?></h1>
            <p class="hero-text">
              <?=htmlspecialchars($loginLang['login_hero_text'], ENT_QUOTES, 'UTF-8');?>
            </p>
            <div class="hero-pills">
              <span class="hero-pill"><?=erp_h('login_pill_inventory', 'Inventory');?></span>
              <span class="hero-pill"><?=erp_h('login_pill_customs', 'Customs');?></span>
              <span class="hero-pill"><?=erp_h('login_pill_production', 'Production');?></span>
              <span class="hero-pill"><?=erp_h('login_pill_finance', 'Finance');?></span>
            </div>
          </div>
          <div class="hero-footer">
            <?=htmlspecialchars(namaPT, ENT_QUOTES, 'UTF-8');?> &middot; <?=htmlspecialchars($loginLang['login_secure_access'], ENT_QUOTES, 'UTF-8');?>
          </div>
	        </aside>

	        <section class="login-card-wrap">
	          <div class="login-card">
	            <div class="mobile-logo">
	              <img src="assets/logo_kb3.png" alt="<?=htmlspecialchars(namaPT, ENT_QUOTES, 'UTF-8');?>">
	            </div>

	            <div class="login-language">
              <form method="post" action="inc/set_language.php">
                <input type="hidden" name="redirect_to" value="<?=htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8');?>">
                <select name="language" aria-label="<?=erp_h('top_nav_language', 'Language');?>" onchange="this.form.submit()">
                  <?php
                  $currentLanguage = isset($_SESSION['language']) ? $_SESSION['language'] : 'en';
                  foreach (erpkb_available_languages() as $code => $meta) {
                    $selected = $currentLanguage === $code ? 'selected' : '';
                    echo '<option value="'.htmlspecialchars($code, ENT_QUOTES, 'UTF-8').'" '.$selected.'>'.htmlspecialchars($meta['label'], ENT_QUOTES, 'UTF-8').'</option>';
                  }
                  ?>
                </select>
	              </form>
	            </div>

	            <p class="login-eyebrow"><?=htmlspecialchars($loginLang['login_welcome_back'], ENT_QUOTES, 'UTF-8');?></p>
	            <h2 class="login-title"><?=htmlspecialchars($loginLang['login_title'], ENT_QUOTES, 'UTF-8');?></h2>
            <p class="login-subtitle">
              <?=htmlspecialchars($loginLang['login_subtitle'], ENT_QUOTES, 'UTF-8');?>
            </p>

            <div class="alert login-alert" id="gagal_login" style="display: none" role="alert">
              <strong><?=htmlspecialchars($loginLang['login_failed_title'], ENT_QUOTES, 'UTF-8');?></strong><br>
	              <span id="login_error_message"><?=htmlspecialchars($loginLang['login_invalid_default'], ENT_QUOTES, 'UTF-8');?></span>
	            </div>

	            <form id="form_login" class="login-form" autocomplete="on">
	              <input type="hidden" id="login_csrf_token" value="<?=htmlspecialchars($_SESSION['login_csrf_token'], ENT_QUOTES, 'UTF-8');?>">

	              <div class="field-group">
	                <label class="field-label" for="username"><?=htmlspecialchars($loginLang['login_username'], ENT_QUOTES, 'UTF-8');?></label>
	                <input type="text" id="username" name="username" class="login-input"
	                  placeholder="<?=htmlspecialchars($loginLang['login_username_placeholder'], ENT_QUOTES, 'UTF-8');?>" autocomplete="username" required autofocus />
	              </div>

	              <div class="field-group">
	                <label class="field-label" for="password"><?=htmlspecialchars($loginLang['login_password'], ENT_QUOTES, 'UTF-8');?></label>
	                <input type="password" id="password" name="password" class="login-input"
	                  placeholder="<?=htmlspecialchars($loginLang['login_password_placeholder'], ENT_QUOTES, 'UTF-8');?>" autocomplete="current-password" required />
	              </div>

	              <div class="login-actions">
	                <button type="submit" id="btn_login" class="btn login-button"><?=htmlspecialchars($loginLang['login_submit'], ENT_QUOTES, 'UTF-8');?></button>
	                <button class="btn login-button loading" id="btn_loading" type="button" disabled style="display: none">
	                  <span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span>
	                  <?=htmlspecialchars($loginLang['login_processing'], ENT_QUOTES, 'UTF-8');?>
	                </button>
	              </div>
	            </form>

	            <p class="login-footnote">
	              © <?= date("Y") ?> <?=htmlspecialchars(namaPT, ENT_QUOTES, 'UTF-8');?>.
	              <?=htmlspecialchars($loginLang['login_developed_by'], ENT_QUOTES, 'UTF-8');?> <a href="https://transbyte.co.id/">Transbyte.co.id</a>
	            </p>
	          </div>
	        </section>
      </section>
    </main>

    <!-- MDB -->
    <script src="assets/login/js/jquery.js"></script>
    <script type="text/javascript" src="mdb/js/mdb.min.js"></script>
    <!-- Custom scripts -->
    <script type="text/javascript">
      var LOGIN_LANG = <?=json_encode($loginLang, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);?>;
	    function showLoginError(message) {
	        $("#login_error_message").text(message || LOGIN_LANG.login_invalid_default);
	        $("#gagal_login").stop(true, true).show().fadeOut(5000);
	    }

	    function setLoginLoading(isLoading) {
	        $("#btn_login").prop("disabled", isLoading).toggle(!isLoading);
	        $("#btn_loading").toggle(isLoading);
	    }

	    $("#form_login").submit(function(e){ 
	        e.preventDefault();
	        var username = $.trim($("#username").val());
	        var password = $("#password").val();

	        if (username === "" || password === "") {
	            showLoginError(LOGIN_LANG.login_required);
	            return false;
	        }

	        setLoginLoading(true);
	        $.ajax({
	          type : "POST",
	          dataType : "json", 
	          url  : "inc/login_new.php", 
	          data : {
	             username : username,
	             password : password,
	             csrf_token : $("#login_csrf_token").val()
	          },
	          
	          success : function(data){  
	            setLoginLoading(false);
	            if (data && data.status === "success") {
	                document.location = "<?=base_index();?>"; 
	            } else {
	               showLoginError(data && data.message ? data.message : LOGIN_LANG.login_invalid_default);
	            }
	          },
	          error : function(){
	            setLoginLoading(false);
	            showLoginError(LOGIN_LANG.login_server_unresponsive);
	          }
	        })
	        return false;
	     });
  </script>
  </body>
</html>

<?php 
} else {
  header("location:".base_index());
  exit;
}
?>
