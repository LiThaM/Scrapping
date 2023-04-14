<?php
$filename = "";
$totalLinks = 0;
$downloadedFiles = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  header('Content-Type: application/json');

  $url = $_POST['url'];
  $extension = $_POST['fileType'];
  $response = array();

  if (!empty($url) && !empty($extension)) {
    $folder = "scrapping-php";
    $cleanUrl = parse_url($url, PHP_URL_HOST);
    $folderUrl = $folder . "/" . $cleanUrl;
    if (!is_dir($folder)) {
      mkdir($folder);
    }
    if (!is_dir($folderUrl)) {
      mkdir($folderUrl);
    }

    // Sanitize input data
    $url = filter_var($url, FILTER_SANITIZE_URL);
    //extensiones que permitimos SANITIZE PROPIO DE EXTENSIONES
    $allowed_extensions = array("jpg", "png", "gif", "jpeg", "pdf");

    if (in_array($extension, $allowed_extensions)) {
      // El archivo tiene una extensión permitida
      $content = file_get_contents($url);
      //Aceptamos tanto links como imagenes
      preg_match_all('/<(a|img).*?(href|src)="([^"]*\.(?:' . $extension . '))"/i', $content, $links);
      $totalLinks = count($links[3]);
      $downloadedFiles = 0;
      foreach ($links[3] as $link) {
        $filename = basename($link);
        if (file_put_contents($folderUrl . '/' . $filename, file_get_contents($link)) !== false) {
          $downloadedFiles++;
        }
      }
      if ($totalLinks > 0) {
        $downloadPercentage = round(($downloadedFiles / $totalLinks) * 100);
      } else {
        $downloadPercentage = 0;
      }
      $response['message'] = "Se han descargado {$downloadedFiles} de {$totalLinks} archivos .{$extension} de la URL: {$url}, la URL local: {$folderUrl}";
      $response['success'] = true;
      $response['downloadPercentage'] = $downloadPercentage;
    } else {
      // El archivo tiene una extensión no permitida
      $response['message'] = "Por favor, introduzca una extension permitida " . implode(",", $allowed_extensions);
      $response['success'] = false;
    }
  } else {
    $response['message'] = "Por favor, complete todos los campos.";
    $response['success'] = false;
  }

  echo json_encode($response);
  exit;
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8" />
  <meta http-equiv="X-UA-Compatible" content="IE=edge" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-KK94CHFLLe+nY2dmCWGMq91rCGa5gtU4mk92HdvYe+M/SXH301p5ILy+dN9+nJOZ" crossorigin="anonymous" />
  <title>Prueba Scrapping</title>
</head>

<body>
  <div class="container p-2">
    <div class="card">
      <div class="card-header">Scrapping</div>
      <div class="card-body">
        <form class="g-3 was-validated" onsubmit="event.preventDefault();" id="form">
          <p class="card-text">
            Introduce URL a Scrapear y extensión a descargar.
          </p>
          <div class="row">
            <div class="col">
              <div class="input-group mb-3">
                <div class="input-group-prepend">
                  <span class="input-group-text" id="basic-addon1">URL</span>
                </div>
                <input type="text" class="form-control" placeholder="https://example.com/" id="url" name="url" required />
                <div class="invalid-feedback">Introduce una URL valida.</div>
              </div>
            </div>
          </div>

          <div class="row">
            <div class="col">
              <div class="input-group mb-3">
                <div class="input-group-prepend">
                  <span class="input-group-text" id="basic-addon1">Ext</span>
                </div>
                <input type="text" class="form-control" placeholder="gif" id="fileType" name="fileType" required />
                <div class="invalid-feedback">
                  Introduce una extensi&oacute;n valida.
                </div>
              </div>
            </div>
          </div>
          <div class="row">
            <div class="col">
              <div class="input-group mb-3 p-2">
                <button class="btn btn-primary" type="submit" id="buttonSend">
                  Descargar
                </button>
              </div>
            </div>
          </div>
        </form>
      </div>
    </div>
    <div class="card invisible mt-2" id="cardStatus">
      <div class="card-header">Resultados</div>
      <div class="card-body">

        <div class="card-text">
          <p id="status"></p>
        </div>
      </div>
    </div>
  </div>
  </div>
  </div>
  <p class="text-center"> &copy; Alejandro Castro Nantes</p>
</body>
<script>
  // Obtener los elementos del formulario
  const form = document.querySelector("form");
  const urlInput = document.getElementById("url");
  const fileTypeInput = document.getElementById("fileType");

  // Agregar un evento de escucha para cuando el formulario se envía
  $('form').on('submit', function(event) {
    // Validar la URL
    event.preventDefault();
    const urlValue = urlInput.value.trim();
    if (!urlValue.startsWith("http://") && !urlValue.startsWith("https://")) {
      alert("Introduce una URL válida (que empiece por http:// o https://)");
      urlInput.classList.add("is-invalid"); // Marcar como inválido
      return;
    } else {
      urlInput.classList.remove("is-invalid"); // Marcar como válido
    }
    // Verificar si se ha introducido una extensión válida
    const fileTypeValue = fileTypeInput.value.trim();
    if (fileTypeValue === "") {
      alert("Introduce una extensión válida");
      fileTypeInput.classList.add("is-invalid"); // Marcar como inválido
      return;
    } else {
      fileTypeInput.classList.remove("is-invalid"); // Marcar como válido
    }

    //Sanemos datos antes de enviar al PHP desde javascript
    SanitizeUrlValue = DOMPurify.sanitize(urlValue);
    SanitizeFileTypeInput = DOMPurify.sanitize(fileTypeValue);
    // Enviar los datos al archivo PHP
    var formData = new FormData();
    formData.append('url', SanitizeUrlValue);
    formData.append('fileType', SanitizeFileTypeInput);
    //Bloqueamos Formulario y activamos el cardStatus para mostrar el porcentaje
    $("#buttonSend").prop('disabled', true);
    $("#cardStatus").removeClass("invisible").addClass('visible', 'bg-success');
    //Ajax para comunicarnos y poder saber el porcentaje de descarga
    $.ajax({
      xhr: function() {
        var xhr = new XMLHttpRequest();
        xhr.upload.addEventListener('progress', function(event) {
          if (event.lengthComputable) {
            var percent = Math.round((event.loaded / event.total) * 100);
            $('#status').text('Procesando archivos ---> ' + percent + '%'); // Actualiza el porcentaje de descarga en tiempo real
          }
        }, false);
        return xhr;
      },
      method: 'POST',
      data: formData,
      processData: false,
      contentType: false,
      dataType: 'json',
      success: function(response) {
        if (response.success) {
          $('#status').text(response.message); // Actualiza el porcentaje de descarga a 100% al finalizar
          alert(response.message);
        } else {
          $('#status').text(response.message); // mensaje errores controlados.
          alert(response.message);
        }
      },
      error: function(xhr, status, error) {
        alert('Ha ocurrido un error al descargar los archivos.');
      }
    });
    $("#buttonSend").prop('disabled', false); // Activamos Boton

  });
</script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/dompurify/3.0.1/purify.min.js" integrity="sha512-TU4FJi5o+epsahLtM9OFRvH2gXmmlzGlysk9wtTFgbYbMvFzh3Cw1l3ubnYIvBiZCC/aurRHS408TeEbcuOoyQ==" crossorigin="anonymous" referrerpolicy="no-referrer"></script>

</html>