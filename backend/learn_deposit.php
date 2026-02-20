<html lang="en">

<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Deposit</title>
  <meta name="keywords" content="">
  <meta name="description" content="">
  <link rel="shortcut icon" type="image/png" href="assets/fav.png">
  <link rel="stylesheet" type="text/css" href="p/bqw777sgxiwug3ex.css" onerror="location.reload()">
  <link rel="stylesheet" type="text/css" href="p/6uggmxepfxguqhgf.css" onerror="location.reload()">
  <link rel="stylesheet" type="text/css" href="p/bootstrap-datetimepicker.min.css" onerror="location.reload()">
  <link rel="stylesheet" type="text/css" href="p/all.min.css" onerror="location.reload()">
  <link rel="stylesheet" type="text/css" href="p/c3gyq3shygmabbke.css" onerror="location.reload()">
  <link rel="stylesheet" href="Add-fund/style.css">
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
  <?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type"); // throw if its logged out
  $in = $_COOKIE['in'];
  if ($in == 1) {
   // header("location: home.php");
  } ?>
  <?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type"); // throw if its unknown
  include 'inc/connect2.php';
  include 'inc/session.php';
  if (!isset($_COOKIE['id'])) {
   // header("location: home.php");
  } ?>
  
</head><!-- userfull js-->

<body>

  <?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
  $p = $_GET['page'];
  echo "<a href='$p'.php' style='position: fixed;border-radius: 8%; background: rgba(0, 0, 0, 0.6);z-index:999999;position: fixed;text-decoration:none;color:white;top:2rem;right:1.2rem;font-size: 1.4rem;padding: 1rem;z-index: 10'><i class='fas fa-times'></i></a>";
  ?>

  <div class="loader-container">
    <div class="loader"></div>
  </div>

  <div class="container">
    <div class="row">
      <div style="margin-top: 4rem;" class="col-md-8 col-md-offset-2">
        <div class="well" style="display:none"id="tab-add">
          <div class="form-group">
            <label for="method" class="control-label">Method</label>
            <select onChange="myFunction()" id="selectform" class="form-control" id="ppm">
              <option value="Commerical Bank Of Ethiopia">Commerical Bank Of Ethiopia</option>
        
              <option value="Awash Bank">Awash Bank</option>
              <option value="Telebirr">Telebirr</option>
            </select>
          </div>
          <div class="form-group fields" id="order_transaction_id">
            <div style="display: flex;"><b>Name</b>
              <div class="tooltipp"><img style="margin-left: .7rem;margin-bottom: 0rem; opacity: .6;" width="10rem"
                  height="10rem" src="assets/favpng_favicon-download-apple-icon-image-format.png">
                <span class="tooltiptextp">Depositor Name</span>
              </div>
            </div>
            <input class="form-control" type="text" value="" name="user" id="floatingTextInput1" required>
          </div>
          <div class="form-group">
            <label for="amount" class="control-label">
              <span id="amount_label">Amount</span>
            </label>
            <input type="number" class="form-control" step="0.01" name="amount" onkeypress="return isNumber(event)"
              id="floatingEmailInput" placeholder="Minimum - 150" required>
          </div>
          <p id="alert" style="display:none;color:red">Minimum - 150</p>
          <input type="button" onclick="trans()" id="transfer"
            style="color:white; font-weight: normal;background:#3474db;padding:15px 35px; border-radius: 10px;border:none"
            value="Done">
        </div>
          <div class="flex flex-col items-center justify-start min-h-fit space-y-4">
    <div class="flex flex-col space-y-2">
      <input 
        type="number" 
        id="am" 
        class="border-2 border-gray-300 rounded-md p-4 w-72" 
        placeholder="(minimum 10 ETB)"
      />
      <p id="warningMessage" style="display: none;" class="text-red-500 text-md ">Amount must be greater than 10 ETB.</p>
    </div>
    <button 
      id="payButton" 
      class="bg-blue-500 text-white font-bold py-6 px-12 rounded-md hover:bg-blue-600"
    >
      Proceed to Payment
    </button>
    <div class="payment-container" id="chapa-inline-form"></div>
  </div>

  
      </div>
    </div>
  </div>
  </div>
  </div>

  <!--table-->

  <div class="container">
    <div class="row">
      <div class="col-md-8 col-md-offset-2">
        <div style="overflow: auto; background:none;width: 100%;margin-top: 2rem; height: 60%;">
          <div id="users-deposit"></div><!-- table-->
          <div style="font-size: 2pc;margin-top: 10%;margin-left: 10%;color:#bbb" id="m"></div>
        </div>
      </div>
    </div>
  </div>
  <style>
    .tooltipp {
      position: relative;
      display: inline-block;
      z-index: 11;
      color: red;
      border-bottom: 1px dotted black;
    }

    .tooltipp .tooltiptextp {
      visibility: hidden;
      width: 120px;
      background-color: black;
      color: #fff;
      text-align: center;
      border-radius: 6px;
      padding: 5px 0;
      /* Position the tooltip */
      position: absolute;
      z-index: 1;
      bottom: 100%;
      left: 50%;
      margin-left: -60px;
    }

    .tooltipp:hover .tooltiptextp {
      visibility: visible;
    }
  </style>
  </div>
  </div>
  </div>
  </div>
  <?php
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type");
$cookieValue = isset($_COOKIE['in']) ? $_COOKIE['in'] : null;
?>
   <script src="//code.tidio.co/viycbw2l0hdk1ufzyuw9fa9qsqri1q8i.js" async></script>

<script>
    let chapa; // Declare chapa instance globally for reuse

    function initializeChapa(amount) {
      chapa = new ChapaCheckout({
        publicKey: 'CHAPUBK-s9JQu74c7hAcdPPGxaAF6aT22Ih4HNtm',
        amount: amount, // Pass the amount dynamically
        currency: 'ETB',
        mobile: '',
        showFlag: true,
        showPaymentMethodsNames: true,
        onSuccessfulPayment: (result, refId) => {
         
          // Make a POST request to submitDepositb.php
     

          const payButton = document.getElementById("payButton");
          payButton.textContent = "Proceed to Payment";

          document.getElementById("chapa-inline-form").style.display = "none";
          
          
              const xhr = new XMLHttpRequest();
          xhr.open("POST", "Add-fund/submitDepositb.php", true);
          xhr.setRequestHeader("Content-Type", "application/json");
          xhr.onreadystatechange = function () {
            if (xhr.readyState === XMLHttpRequest.DONE) {
              if (xhr.status === 200) {
                   window.location.href = "https://paxyo.com/smm.php";
     
                   const amountInput = document.getElementById("am");
                   const payButton = document.getElementById("payButton");
                    payButton.style.display="block"
                  payButton.textContent = "Proceed to Payment";
                  payButton.disabled = true;
                  amountInput.value = '';
                         try {
                             
             const response = xhr.responseText;

  // Regular expression to match the HTTPS link
  const regex = /(https:\/\/[^\s]+)/;
  const match = response.match(regex);

  // If a match is found, log the URL
  if (match && match[0]) {
     window.location.href= match[0];
     
    
  } else {
    console.error("No valid HTTPS URL found in the response.");
  }
            } catch (e) {
              console.error("Failed to parse response as JSON:", xhr.responseText);
            }
              } else {
                console.error("Error recording deposit:", xhr.responseText);
              }
            }
          };
          
          // Prepare the data to send
          const data = JSON.stringify({
            amount: amount,
            referenceId: refId,
          });
          
          xhr.send(data);
        },
        onPaymentFailure: (errorMessage) => {
          window.alert("Please try again later");
          const payButton = document.getElementById("payButton");
      payButton.style.display="block"
          payButton.textContent = "Try Again";
          payButton.disabled = false;

          document.getElementById("chapa-inline-form").style.display = "none";
        },
      });

      chapa.initialize();
    }

    function startPayment() {
      const amountInput = document.getElementById("am");
      const amount = parseFloat(amountInput.value); // Parse the value to a number
      const warningMessage = document.getElementById("warningMessage");

      if (!amount || amount <= 1) {
          
        warningMessage.style.display = "block"; // Show the warning message
       
        return;
      }
      

      warningMessage.style.display = "none"; // Hide the warning message if valid
      const payButton = document.getElementById("payButton");
      payButton.style.display="none"
      payButton.disabled = true;
      document.getElementById("chapa-inline-form").style.display = "block";

      initializeChapa(amount);
      chapa.startPayment();
    }

    // Hide payment container by default
    document.getElementById("chapa-inline-form").style.display = "none";

    // Attach the event listener to the button
    document.getElementById("payButton").addEventListener("click", startPayment);
  
</script>


  <script src="Add-fund/script.js"></script>
      <script src="https://js.chapa.co/v1/inline.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/tailwindcss@2.2.15/dist/tailwind.min.css" rel="stylesheet">
  <script type="text/javascript" src="p/jquery.min.js"></script>
  <!--<script src="https://cdn.bootcss.com/jquery/3.3.1/jquery.js"></script> -->
  <script src="https://unpkg.com/sweetalert/dist/sweetalert.min.js"></script>
  <script type="text/javascript" src="https://cdn.mypanel.link/libs/jquery/1.12.4/jquery.min.js"></script>
</body>



</html>