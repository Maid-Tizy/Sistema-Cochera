/**
 * SISTEMA DE CONTROL DE COCHERA
 * JavaScript para Dashboard - Funcionalidad interactiva
 */

// Variables globales
let modalEspacio = null;
let modalBuscarPlaca = null;
let espacioActual = null;
let datosServicioActual = null;


// Inicialización cuando el DOM está listo
document.addEventListener("DOMContentLoaded", function () {
  // Inicializar modales
  modalEspacio = new bootstrap.Modal(document.getElementById("modalEspacio"));
  modalBuscarPlaca = new bootstrap.Modal(
    document.getElementById("modalBuscarPlaca")
  );

  // Event listeners
  document
    .getElementById("btnPararServicio")
    .addEventListener("click", function () {
      modalBuscarPlaca.show();
    });

  // Convertir placa a mayúsculas automáticamente
  document
    .getElementById("placaBusqueda")
    .addEventListener("input", function () {
      this.value = this.value.toUpperCase();
    });

  document.getElementById("placa").addEventListener("input", function () {
    this.value = this.value.toUpperCase();
  });

  // Actualizar dashboard cada 60 segundos
  //setInterval(actualizarDashboard, 60000);
});

/**
 * Abre la ventana flotante para gestionar un espacio específico
 */
function abrirVentanaEspacio(idEspacio) {
  espacioActual = idEspacio;

  // Actualizar título del modal
  document.getElementById("tituloModal").textContent = `Cochera ${idEspacio}`;

  // Mostrar loading
  mostrarLoading(true);

  // Obtener datos del espacio
  fetch("../api/espacio_detalle.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ id_espacio: idEspacio }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        cargarDatosEspacio(data.espacio);
      } else {
        mostrarError("Error al cargar los datos del espacio");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      mostrarError("Error de conexión");
    })
    .finally(() => {
      mostrarLoading(false);
      modalEspacio.show();
    });
}

/**
 * Carga los datos del espacio en el modal
 */
function cargarDatosEspacio(espacio) {
  // Limpiar formulario
  limpiarFormulario();

  // Cargar datos básicos
  document.getElementById("idEspacio").value = espacio.id_espacio;

  if (espacio.id_alquiler) {
    // Espacio ocupado - cargar datos del alquiler activo
    document.getElementById("idAlquiler").value = espacio.id_alquiler;
    document.getElementById("placa").value = espacio.placa || "";
    document.getElementById("codigo").value = espacio.codigo || "";
    document.getElementById("fechaRegistro").value =
      espacio.fecha_ingreso_formato || "";
    document.getElementById("horaIngreso").value =
      espacio.hora_ingreso_formato || "";

    // Deshabilitar campos de entrada
    document.getElementById("placa").disabled = true;
    document.getElementById("precioHora").disabled = true;

    // Mostrar botones apropiados
    mostrarBotones(["btnParar"]);
  } else {
    // Espacio libre - preparar para nuevo servicio
    document.getElementById("fechaRegistro").value = obtenerFechaActual();
    document.getElementById("horaIngreso").value = obtenerHoraActual();
    document.getElementById("codigo").value = "Se generará automáticamente";

    // Habilitar campos de entrada
    document.getElementById("placa").disabled = false;
    document.getElementById("precioHora").disabled = false;

    // Mostrar botones apropiados
    mostrarBotones(["btnIniciar"]);
  }

  datosServicioActual = espacio;
}

/**
 * Inicia un nuevo servicio
 */
/**
 * Inicia un nuevo servicio y opcionalmente genera el comprobante de entrada.
 * @param {boolean} imprimirComprobante - Si true, cierra el modal y abre/descarga el comprobante.
 */
function iniciarServicio(imprimirComprobante = false) {
  const placa = document.getElementById("placa").value.trim();
  const precioHora = parseFloat(document.getElementById("precioHora").value);

  if (!placa) {
    mostrarError("Por favor ingrese la placa del vehículo");
    return;
  }

  if (!precioHora || precioHora <= 0) {
    mostrarError("Por favor ingrese un precio por hora válido");
    return;
  }

  // Deshabilitar botón y mostrar loading
  const btnIniciar = document.getElementById("btnIniciar");
  btnIniciar.disabled = true;
  btnIniciar.innerHTML =
    '<i class="fas fa-spinner fa-spin me-1"></i>Iniciando...';

  // Enviar petición
  fetch("../api/iniciar_servicio.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      id_espacio: espacioActual,
      placa: placa,
      precio_hora: precioHora,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        // Actualizar interfaz
        document.getElementById("codigo").value = data.codigo;
        document.getElementById("placa").disabled = true;
        document.getElementById("precioHora").disabled = true;
        document.getElementById("idAlquiler").value = data.id_alquiler;

        // Actualizar grid de espacios
        actualizarEspacioEnGrid(espacioActual, "ocupado", placa);

        // Guardar datos para comprobante (entrada)
        datosServicioActual = {
          tipo: "entrada",
          espacio: espacioActual,
          placa: placa,
          codigo: data.codigo,
          fecha_ingreso: document.getElementById("fechaRegistro").value,
          hora_ingreso: document.getElementById("horaIngreso").value,
          precio_hora: precioHora,
        };

        // Si se solicita imprimir automáticamente, cerrar modal y abrir comprobante
        if (imprimirComprobante) {
          try {
            modalEspacio.hide();
          } catch (e) {}
          const datos = {
            tipo: "entrada",
            espacio: datosServicioActual.espacio,
            placa: datosServicioActual.placa,
            codigo: datosServicioActual.codigo,
            fecha_ingreso: datosServicioActual.fecha_ingreso,
            hora_ingreso: datosServicioActual.hora_ingreso,
            precio_hora: datosServicioActual.precio_hora,
          };
          generarComprobante(datos);
        } else {
          // Cambiar botones para permitir imprimir manualmente
          mostrarBotones(["btnImprimirEntrada"]);
          mostrarExito("Servicio iniciado correctamente");
        }
      } else {
        mostrarError(data.message || "Error al iniciar el servicio");
        btnIniciar.disabled = false;
        btnIniciar.innerHTML = '<i class="fas fa-play me-1"></i>Iniciar';
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      mostrarError("Error de conexión");
      btnIniciar.disabled = false;
      btnIniciar.innerHTML = '<i class="fas fa-play me-1"></i>Iniciar';
    });
}

/**
 * Para un servicio activo y opcionalmente genera el comprobante de salida.
 * @param {boolean} imprimirComprobante - Si true, cierra el modal y abre/descarga el comprobante.
 */
function pararServicio(imprimirComprobante = false) {
  const idAlquiler = document.getElementById("idAlquiler").value;
  const precioHora =
    parseFloat(document.getElementById("precioHora").value) || 5.0;

  if (!idAlquiler) {
    mostrarError("No se encontró el alquiler activo");
    return;
  }

  const btnParar = document.getElementById("btnParar");
  if (btnParar) {
    btnParar.disabled = true;
    btnParar.innerHTML =
      '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
  }

  fetch("../api/parar_servicio.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({
      id_alquiler: idAlquiler,
      precio_hora: precioHora,
    }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success) {
        const datosPago = data.datos_pago;

        document.getElementById("fechaSalida").value = formatearFecha(
          datosPago.fecha_salida
        );
        document.getElementById("horaSalida").value = formatearHora(
          datosPago.hora_salida
        );
        // Mostrar minutos reales en la UI
        document.getElementById("duracion").value =
          datosPago.minutos_reales + " minutos";
        document.getElementById("totalPagar").value =
          "S/. " + datosPago.importe.toFixed(2);

        // Guardar todos los datos para el comprobante (incluyendo minutos reales y cobrados)
        datosServicioActual = {
          tipo: "salida",
          espacio: espacioActual,
          placa: document.getElementById("placa").value,
          codigo: document.getElementById("codigo").value,
          fecha_ingreso: document.getElementById("fechaRegistro").value,
          hora_ingreso: document.getElementById("horaIngreso").value,
          fecha_salida: formatearFecha(datosPago.fecha_salida),
          hora_salida: formatearHora(datosPago.hora_salida),
          precio_hora: document.getElementById("precioHora").value,
          minutos_reales: datosPago.minutos_reales,
          minutos_cobrados: datosPago.minutos_cobrados,
          horas_cobradas: datosPago.horas_cobradas,
          importe: datosPago.importe,
        };

        actualizarEspacioEnGrid(espacioActual, "libre");

        mostrarExito("Servicio finalizado correctamente");

        // Si se solicita imprimir, cerrar modal y generar comprobante
        if (imprimirComprobante) {
          try {
            modalEspacio.hide();
          } catch (e) {}
          const datos = {
            tipo: "salida",
            espacio: espacioActual,
            placa: document.getElementById("placa").value,
            codigo: document.getElementById("codigo").value,
            fecha_ingreso: document.getElementById("fechaRegistro").value,
            hora_ingreso: document.getElementById("horaIngreso").value,
            fecha_salida: document.getElementById("fechaSalida").value,
            hora_salida: document.getElementById("horaSalida").value,
            precio_hora: document.getElementById("precioHora").value,
            minutos_reales: datosPago.minutos_reales,
            minutos_cobrados: datosPago.minutos_cobrados,
            horas_cobradas: datosPago.horas_cobradas,
            total_pagar: "S/. " + datosPago.importe.toFixed(2),
          };
          generarComprobante(datos);
        } else {
          mostrarBotones(["btnImprimirSalida"]);
        }
      } else {
        mostrarError(data.message || "Error al finalizar el servicio");
        if (btnParar) {
          btnParar.disabled = false;
          btnParar.innerHTML = '<i class="fas fa-stop me-1"></i>Parar';
        }
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      mostrarError("Error de conexión");
      if (btnParar) {
        btnParar.disabled = false;
        btnParar.innerHTML = '<i class="fas fa-stop me-1"></i>Parar';
      }
    });
}

/**
 * Busca un servicio activo por placa
 */
function buscarPorPlaca() {
  const placa = document.getElementById("placaBusqueda").value.trim();

  if (!placa) {
    mostrarErrorBusqueda("Por favor ingrese una placa");
    return;
  }

  // Enviar petición
  fetch("../api/buscar_por_placa.php", {
    method: "POST",
    headers: {
      "Content-Type": "application/json",
    },
    body: JSON.stringify({ placa: placa }),
  })
    .then((response) => response.json())
    .then((data) => {
      if (data.success && data.alquiler) {
        // Cerrar modal de búsqueda
        modalBuscarPlaca.hide();

        // Abrir modal del espacio encontrado
        setTimeout(() => {
          abrirVentanaEspacio(data.alquiler.id_espacio);
        }, 300);
      } else {
        mostrarErrorBusqueda("No se encontró un servicio activo con esa placa");
      }
    })
    .catch((error) => {
      console.error("Error:", error);
      mostrarErrorBusqueda("Error de conexión");
    });
}

/**
 * Imprime el comprobante de entrada
 */
function imprimirEntrada() {
  // Preferir datos guardados en datosServicioActual si existen (evita 'datos is not defined')
  const datos =
    datosServicioActual && datosServicioActual.tipo === "entrada"
      ? {
          tipo: "entrada",
          espacio: datosServicioActual.espacio || espacioActual,
          placa:
            datosServicioActual.placa || document.getElementById("placa").value,
          codigo:
            datosServicioActual.codigo ||
            document.getElementById("codigo").value,
          fecha_ingreso:
            datosServicioActual.fecha_ingreso ||
            document.getElementById("fechaRegistro").value,
          hora_ingreso:
            datosServicioActual.hora_ingreso ||
            document.getElementById("horaIngreso").value,
          precio_hora:
            datosServicioActual.precio_hora ||
            document.getElementById("precioHora").value,
        }
      : {
          tipo: "entrada",
          espacio: espacioActual,
          placa: document.getElementById("placa").value,
          codigo: document.getElementById("codigo").value,
          fecha_ingreso: document.getElementById("fechaRegistro").value,
          hora_ingreso: document.getElementById("horaIngreso").value,
          precio_hora: document.getElementById("precioHora").value,
        };

  generarComprobante(datos);
}

/**
 * Imprime el comprobante de salida
 */
function imprimirSalida() {
  const datos = {
    tipo: "salida",
    espacio: espacioActual,
    placa: document.getElementById("placa").value,
    codigo: document.getElementById("codigo").value,
    fecha_ingreso: document.getElementById("fechaRegistro").value,
    hora_ingreso: document.getElementById("horaIngreso").value,
    fecha_salida: document.getElementById("fechaSalida").value,
    hora_salida: document.getElementById("horaSalida").value,
    precio_hora: document.getElementById("precioHora").value,
    minutos_reales: datosServicioActual.minutos_reales,
    minutos_cobrados: datosServicioActual.minutos_cobrados,
    horas_cobradas: datosServicioActual.horas_cobradas,
    total_pagar: document.getElementById("totalPagar").value,
  };

  generarComprobante(datos);
}

/**
 * Genera y muestra el comprobante
 */
function generarComprobante(datos) {
  // Abrir nueva ventana para el comprobante
  const ventana = window.open("", "_blank", "width=500,height=700");

  // Guardar referencia para recargar después
  ventana.onbeforeunload = () => {
    location.reload();
  };

  // Crear el HTML del comprobante usando concatenación para evitar conflictos
  let html = "<!DOCTYPE html>";
  html += "<html>";
  html += "<head>";
  html += "<title>Comprobante - Sistema de Cochera</title>";
  html +=
    '<script src="https://cdnjs.cloudflare.com/ajax/libs/jspdf/2.5.1/jspdf.umd.min.js"></script>';
  html += "<style>";
  html +=
    "body { font-family: Arial, sans-serif; padding: 20px; background: white; margin: 0; }";
  html +=
    ".header { text-align: center; border-bottom: 2px solid #000; padding-bottom: 15px; margin-bottom: 25px; }";
  html += ".header h2 { margin: 0 0 10px 0; color: #333; }";
  html += ".header h3 { margin: 0; color: #555; }";
  html += ".content { margin: 20px 0; }";
  html +=
    ".row { display: flex; justify-content: space-between; margin: 10px 0; padding: 8px; border-bottom: 1px solid #eee; }";
  html += ".row:nth-child(even) { background-color: #f9f9f9; }";
  html += ".row strong { color: #333; }";
  html +=
    ".total { font-size: 20px; font-weight: bold; text-align: center; background: #e8f5e8; padding: 20px; margin-top: 25px; border: 2px solid #28a745; border-radius: 8px; color: #155724; }";
  html +=
    ".warning { text-align: center; margin-top: 30px; padding: 20px; background: #fff3cd; border: 2px solid #ffeaa7; border-radius: 8px; color: #856404; font-weight: bold; }";
  html += ".buttons { text-align: center; margin-top: 30px; padding: 20px; }";
  html +=
    ".btn { background: #007bff; color: white; border: none; padding: 12px 25px; margin: 0 8px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 14px; transition: background 0.3s; }";
  html += ".btn:hover { background: #0056b3; transform: translateY(-1px); }";
  html += ".btn-success { background: #28a745; }";
  html += ".btn-success:hover { background: #1e7e34; }";
  html += ".btn-secondary { background: #6c757d; }";
  html += ".btn-secondary:hover { background: #545b62; }";
  html +=
    "@media print { .no-print { display: none !important; } body { margin: 0; background: white !important; } .buttons { display: none !important; } }";
  html += "</style>";
  html += "</head>";
  html += "<body>";
  html += '<div id="comprobante-content">';
  html += '<div class="header">';
  html += "<h2>SISTEMA DE COCHERA</h2>";
  html += "<h3>COMPROBANTE DE " + datos.tipo.toUpperCase() + "</h3>";
  html += "</div>";
  html += '<div class="content">';
  html +=
    '<div class="row"><strong>Cochera:</strong> <span>' +
    datos.espacio +
    "</span></div>";
  html +=
    '<div class="row"><strong>Placa:</strong> <span>' +
    datos.placa +
    "</span></div>";
  html +=
    '<div class="row"><strong>Código:</strong> <span>' +
    datos.codigo +
    "</span></div>";
  html +=
    '<div class="row"><strong>Fecha Ingreso:</strong> <span>' +
    datos.fecha_ingreso +
    "</span></div>";
  html +=
    '<div class="row"><strong>Hora Ingreso:</strong> <span>' +
    datos.hora_ingreso +
    "</span></div>";
  html +=
    '<div class="row"><strong>Precio por Hora:</strong> <span>S/. ' +
    datos.precio_hora +
    "</span></div>";

  if (datos.tipo === "salida") {
    html +=
      '<div class="row"><strong>Fecha Salida:</strong> <span>' +
      datos.fecha_salida +
      "</span></div>";
    html +=
      '<div class="row"><strong>Hora Salida:</strong> <span>' +
      datos.hora_salida +
      "</span></div>";
    html +=
      '<div class="row"><strong>Duración (Actual):</strong> <span>' +
      datos.minutos_reales +
      " minutos</span></div>";
    html +=
      '<div class="row"><strong>Duración (Cobrada):</strong> <span>' +
      datos.minutos_cobrados +
      " minutos (" +
      datos.horas_cobradas +
      " hora" +
      (datos.horas_cobradas > 1 ? "s" : "") +
      ")</span></div>";
    html += '<div class="total">TOTAL A PAGAR: ' + datos.total_pagar + "</div>";
  } else {
    html +=
      '<div class="warning">⚠️ IMPORTANTE: Conserve este comprobante hasta la salida del vehículo</div>';
  }

  html += "</div>";
  html += "</div>";
  html += '<div class="buttons no-print">';
  html += '<button class="btn" onclick="window.print()">Imprimir</button>';
  html += '<button class="btn btn-secondary" onclick="if(window.opener && !window.opener.closed){window.opener.location.href = window.opener.location.href;} window.close();">Cerrar</button>';
  html += "</div>";
  html += "<script>";
  html += "const datos = " + JSON.stringify(datos) + ";";
  html += "function descargarPDF() {";
  html += '    if (typeof window.jspdf === "undefined") {';
  html +=
    '        alert("Cargando librerías PDF... Por favor intente nuevamente en un momento.");';
  html += "        return;";
  html += "    }";
  html += "    try {";
  html += "        const { jsPDF } = window.jspdf;";
  html += "        const pdf = new jsPDF();";
  html += "        pdf.setFontSize(18);";
  html += '        pdf.setFont(undefined, "bold");';
  html +=
    '        pdf.text("SISTEMA DE COCHERA", 105, 25, { align: "center" });';
  html += "        pdf.setFontSize(16);";
  html +=
    '        pdf.text("COMPROBANTE DE ' +
    datos.tipo.toUpperCase() +
    '", 105, 40, { align: "center" });';
  html += "        pdf.setLineWidth(0.5);";
  html += "        pdf.line(20, 50, 190, 50);";
  html += "        let y = 70;";
  html += "        const lineHeight = 15;";
  html += "        pdf.setFontSize(12);";
  html += '        pdf.setFont(undefined, "bold");';
  html += '        pdf.text("Cochera:", 25, y);';
  html += '        pdf.setFont(undefined, "normal");';
  html += '        pdf.text("' + datos.espacio + '", 70, y);';
  html += "        y += lineHeight;";
  html += '        pdf.setFont(undefined, "bold");';
  html += '        pdf.text("Placa:", 25, y);';
  html += '        pdf.setFont(undefined, "normal");';
  html += '        pdf.text("' + datos.placa + '", 70, y);';
  html += "        y += lineHeight;";
  html += '        pdf.setFont(undefined, "bold");';
  html += '        pdf.text("Código:", 25, y);';
  html += '        pdf.setFont(undefined, "normal");';
  html += '        pdf.text("' + datos.codigo + '", 70, y);';
  html += "        y += lineHeight;";
  html += '        pdf.setFont(undefined, "bold");';
  html += '        pdf.text("Fecha Ingreso:", 25, y);';
  html += '        pdf.setFont(undefined, "normal");';
  html += '        pdf.text("' + datos.fecha_ingreso + '", 80, y);';
  html += "        y += lineHeight;";
  html += '        pdf.setFont(undefined, "bold");';
  html += '        pdf.text("Hora Ingreso:", 25, y);';
  html += '        pdf.setFont(undefined, "normal");';
  html += '        pdf.text("' + datos.hora_ingreso + '", 75, y);';
  html += "        y += lineHeight * 1.5;";
  html += '        pdf.setFont(undefined, "bold");';
  html += '        pdf.text("Precio por Hora:", 25, y);';
  html += '        pdf.setFont(undefined, "normal");';
  html += '        pdf.text("S/. " + datos.precio_hora, 75, y);';
  html += "        y += lineHeight;";

  if (datos.tipo === "salida") {
    html += '        pdf.setFont(undefined, "bold");';
    html += '        pdf.text("Fecha Salida:", 25, y);';
    html += '        pdf.setFont(undefined, "normal");';
    html += '        pdf.text("' + datos.fecha_salida + '", 80, y);';
    html += "        y += lineHeight;";
    html += '        pdf.setFont(undefined, "bold");';
    html += '        pdf.text("Hora Salida:", 25, y);';
    html += '        pdf.setFont(undefined, "normal");';
    html += '        pdf.text("' + datos.hora_salida + '", 75, y);';
    html += "        y += lineHeight;";
    html += '        pdf.setFont(undefined, "bold");';
    html += '        pdf.text("Duración (Actual):", 25, y);';
    html += '        pdf.setFont(undefined, "normal");';
    html += '        pdf.text("' + datos.minutos_reales + ' minutos", 80, y);';
    html += "        y += lineHeight;";
    html += '        pdf.setFont(undefined, "bold");';
    html += '        pdf.text("Duración (Cobrada):", 25, y);';
    html += '        pdf.setFont(undefined, "normal");';
    html +=
      '        pdf.text("' +
      datos.minutos_cobrados +
      " minutos (" +
      datos.horas_cobradas +
      ' h)", 90, y);';
    html += "        y += lineHeight * 2;";
    html += "        pdf.setDrawColor(40, 167, 69);";
    html += "        pdf.setFillColor(232, 245, 232);";
    html += '        pdf.rect(20, y - 8, 170, 25, "FD");';
    html += "        pdf.setFontSize(16);";
    html += '        pdf.setFont(undefined, "bold");';
    html += "        pdf.setTextColor(21, 87, 36);";
    html +=
      '        pdf.text("TOTAL A PAGAR: ' +
      datos.total_pagar +
      '", 105, y + 5, { align: "center" });';
    html += "        pdf.setTextColor(0, 0, 0);";
  } else {
    html += "        pdf.setDrawColor(255, 193, 7);";
    html += "        pdf.setFillColor(255, 243, 205);";
    html += '        pdf.rect(20, y, 170, 30, "FD");';
    html += "        pdf.setFontSize(12);";
    html += '        pdf.setFont(undefined, "bold");';
    html += "        pdf.setTextColor(133, 100, 4);";
    html += '        pdf.text("⚠️ IMPORTANTE:", 25, y + 12);';
    html += '        pdf.setFont(undefined, "normal");';
    html += '        pdf.text("Conserve este comprobante hasta", 25, y + 22);';
    html += '        pdf.text("la salida del vehículo", 25, y + 30);';
    html += "        pdf.setTextColor(0, 0, 0);";
  }

  html += "        pdf.setFontSize(9);";
  html += '        pdf.setFont(undefined, "normal");';
  html +=
    '        pdf.text("Sistema de Control de Cochera", 105, 270, { align: "center" });';
  html +=
    '        pdf.text("Generado: " + new Date().toLocaleString("es-PE"), 105, 280, { align: "center" });';
  html +=
    '        const fechaHora = new Date().toISOString().slice(0, 16).replace("T", "_").replace(":", "-");';
  html +=
    '        const nombreArchivo = "comprobante_' +
    datos.tipo +
    "_" +
    datos.placa +
    '_" + fechaHora + ".pdf";';
  html += "        pdf.save(nombreArchivo);";
  html += "    } catch (error) {";
  html += '        alert("Error al generar el PDF: " + error.message);';
  html += '        console.error("Error PDF:", error);';
  html += "    }";
  html += "}";
  html += "window.onload = function() {";
  html += '    console.log("Comprobante cargado correctamente");';
  html +=
    '    console.log("Botones disponibles: ", document.querySelectorAll(".btn").length);';
  html += "};";
  html += "</script>";
  html += "</body>";
  html += "</html>";

  ventana.document.write(html);
  ventana.document.close();
}

// === FUNCIONES UTILITARIAS ===

function limpiarFormulario() {
  document.getElementById("formServicio").reset();
  document.getElementById("idEspacio").value = "";
  document.getElementById("idAlquiler").value = "";
  document.getElementById("placa").disabled = false;
  document.getElementById("precioHora").disabled = false;
}

function mostrarBotones(botones) {
  // Ocultar todos los botones
  ["btnIniciar", "btnImprimirEntrada", "btnParar", "btnImprimirSalida"].forEach(
    (id) => {
      try {
        document.getElementById(id).style.display = "none";
        document.getElementById(id).disabled = false;
      } catch (e) {}
    }
  );

  // Mostrar botones específicos
  botones.forEach((id) => {
    try {
      document.getElementById(id).style.display = "inline-block";
    } catch (e) {}
  });
}

function actualizarEspacioEnGrid(idEspacio, nuevoEstado, placa = "") {
  const elemento = document.querySelector(`[data-id="${idEspacio}"]`);
  if (!elemento) return;

  // Remover clases de estado anteriores
  elemento.classList.remove(
    "espacio-libre",
    "espacio-ocupado",
    "espacio-mantenimiento"
  );

  // Agregar nueva clase y actualizar contenido
  switch (nuevoEstado) {
    case "libre":
      elemento.classList.add("espacio-libre");
      elemento.setAttribute("data-estado", "L");
      try {
        elemento.querySelector(".espacio-estado").textContent = "Disponible";
      } catch (e) {}
      try {
        elemento.querySelector(".espacio-icono i").className = "fas fa-parking";
      } catch (e) {}
      // Remover placa si existe
      const placaElemento = elemento.querySelector(".espacio-placa");
      if (placaElemento) placaElemento.remove();
      break;

    case "ocupado":
      elemento.classList.add("espacio-ocupado");
      elemento.setAttribute("data-estado", "O");
      try {
        elemento.querySelector(".espacio-estado").textContent = "Ocupado";
      } catch (e) {}
      try {
        elemento.querySelector(".espacio-icono i").className = "fas fa-car";
      } catch (e) {}
      // Agregar placa
      if (placa && !elemento.querySelector(".espacio-placa")) {
        const placaDiv = document.createElement("div");
        placaDiv.className = "espacio-placa";
        placaDiv.textContent = placa;
        elemento.appendChild(placaDiv);
      }
      break;
  }
}

function obtenerFechaActual() {
  const fecha = new Date();
  return fecha.toLocaleDateString("es-PE");
}

function obtenerHoraActual() {
  const fecha = new Date();
  return fecha.toLocaleTimeString("es-PE", {
    hour: "2-digit",
    minute: "2-digit",
  });
}

function formatearFecha(fecha) {
  return new Date(fecha).toLocaleDateString("es-PE");
}

function formatearHora(hora) {
  return hora.substring(0, 5); // HH:MM
}

function mostrarLoading(mostrar) {
  // Implementar loading spinner si es necesario
}

function mostrarError(mensaje) {
  // Crear o actualizar alerta de error en el modal
  const alerta = document.createElement("div");
  alerta.className = "alert alert-danger alert-dismissible fade show";
  alerta.innerHTML = `
        <i class="fas fa-exclamation-triangle me-2"></i>
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

  // Insertar al inicio del modal-body
  const modalBody = document.querySelector("#modalEspacio .modal-body");
  const alertaExistente = modalBody ? modalBody.querySelector(".alert") : null;
  if (alertaExistente) alertaExistente.remove();
  if (modalBody) modalBody.insertBefore(alerta, modalBody.firstChild);
}

function mostrarExito(mensaje) {
  const alerta = document.createElement("div");
  alerta.className = "alert alert-success alert-dismissible fade show";
  alerta.innerHTML = `
        <i class="fas fa-check-circle me-2"></i>
        ${mensaje}
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    `;

  const modalBody = document.querySelector("#modalEspacio .modal-body");
  const alertaExistente = modalBody ? modalBody.querySelector(".alert") : null;
  if (alertaExistente) alertaExistente.remove();
  if (modalBody) modalBody.insertBefore(alerta, modalBody.firstChild);
}

function mostrarErrorBusqueda(mensaje) {
  const alerta = document.getElementById("alertaBusqueda");
  if (!alerta) return;
  alerta.className = "alert alert-danger";
  alerta.innerHTML = `<i class="fas fa-exclamation-triangle me-2"></i>${mensaje}`;
  alerta.style.display = "block";
}

function actualizarDashboard() {
  // Recargar la página para obtener datos actualizados
  // En una implementación más avanzada, esto sería una petición AJAX
  location.reload();
}
