<!DOCTYPE html>
<html lang="es">
<head>
 <meta charset="UTF-8">
 <title>SELCOPU</title>
</head>
<body>
<?php
setlocale(LC_CTYPE, 'es');
require_once('numeroletras.php');
	$clave= "fccpv20161123";
	$host= "localhost";
    $user= "root_fccpv";
    $DB= "fccpv";
    $conx= mysqli_connect($host,$user,$clave,$DB);
// Falló el intento de conexión
if ($conx->connect_errno) {
    echo "Lo sentimos, este sitio web está experimentando problemas.";
    echo "Error: Fallo al conectarse a MySQL debido a: \n";
    echo "Errno: " . $mysqli->connect_errno . "\n";
    echo "Error: " . $mysqli->connect_error . "\n";
    exit;
}
	
// recibe el numero del acta a imprimir
	$codigo_seguridad = $_REQUEST['organismo'];
	
// averigua la elección vigente
$sql = "SELECT id, status, periodo, fechaEleccion, cronograma FROM elecciones WHERE status = 1";
if (!$resultado = $conx->query($sql)) {
    // La consulta falló. 
    echo "Lo sentimos, este sitio web está experimentando problemas. La consulta a la Tabla Elecciones falló.";
    echo "Query: " . $sql . "\n";
    echo "Errno: " . $conx->errno . "\n";
    echo "Error: " . $conx->error . "\n";
    exit;
}
if ($resultado->num_rows == 0) {
    // No hay elección activa 
    echo "ERROR! No hay una Elección activa en estos momentos. Contacte a algún miembro de la Comisión Electoral Nacional.";
    exit;
}

$eleccion = $resultado->fetch_assoc();
$elec = $eleccion['id'];
$fechaEleccion = $eleccion['fechaEleccion'];
$periodo = "PERÍODO ELECTORAL ".$eleccion['periodo'];
$cronograma = $eleccion['cronograma'];
$resultado->free();

// *************************************************
// obtiene los datos relacionados a la información que se va a imprimir
$sql = 'SELECT id_organismo, id_estado, id_nucleo FROM totalizacion WHERE codigo_seguridad = '.'"'.$codigo_seguridad.'"';
if (!$resultado = $conx->query($sql)) {
    // La consulta falló. 
    echo "Lo sentimos, este sitio web está experimentando problemas. La consulta a la Tabla Totalizacion falló.";
    echo "Query: " . $sql . "\n";
    echo "Errno: " . $conx->errno . "\n";
    echo "Error: " . $conx->error . "\n";
    exit;
}
if ($resultado->num_rows == 0) {
    // No hay elección activa 
    echo "ERROR! No hay datos para este Código de Seguridad. Contacte a algún miembro de la Comisión Electoral Nacional: ".$codigo_seguridad.".";
    exit;
}

$totaliza = $resultado->fetch_assoc();
$id_organismo = $totaliza['id_organismo'];
$id_estado      = $totaliza['id_estado'] ? $totaliza['id_estado'] : 0 ;// Modificado por Arquimedes
$id_nucleo      = $totaliza['id_nucleo'] ? $totaliza['id_nucleo'] : 0 ;// Modificado por Arquimedes
$resultado->free();

// averigua el nombre del estado
$sql = "SELECT estado, hasDependiente FROM estados WHERE id = $id_estado";
if (!$resultado = $conx->query($sql)) {
    // La consulta falló. 
    echo "Lo sentimos, este sitio web está experimentando problemas. La consulta a la Tabla Totalizacion falló.";
    echo "Query: " . $sql . "\n";
    echo "Errno: " . $conx->errno . "\n";
    echo "Error: " . $conx->error . "\n";
    exit;
}

$estado_requery = $resultado->fetch_assoc();
$estado = $estado_requery['estado'];
$hasDependiente = $estado_requery['hasDependiente'];
$resultado->free();

// si el acta es para un núcleo independiente busca su nombre
$nucleo = ' ';
if($id_organismo == 5 and $hasDependiente == 0) {
	$sql = "SELECT nombre FROM nucleos WHERE id = $id_nucleo";
	if (!$resultado = $conx->query($sql)) {
		// La consulta falló. 
		echo "Lo sentimos, este sitio web está experimentando problemas. La consulta a la Tabla Nucleos falló.";
		echo "Query: " . $sql . "\n";
		echo "Errno: " . $conx->errno . "\n";
		echo "Error: " . $conx->error . "\n";
		exit;
	}	
	$nucleo_requery = $resultado->fetch_assoc();
	$nucleo = $nucleo_requery['nombre'];
	$resultado->free();
}

date_default_timezone_set ('America/Caracas');
setlocale(LC_TIME,"spanish");
$time = time();
$hora = (($time+date('Z'))%86400 < 43200 ? ' am' : ' pm');
$fijo1 = "En la Ciudad de ";
$fijo2 = "el día ";
$fijo2 .= strftime("%d de %B de %Y").", la Comisión Electoral ";
$fijo5 = "procedió a examinar las actas de escrutinios recibidas, correspondiente al proceso electoral celebrado en fecha ";
$fijo5 .= strftime("%d de %B de %Y",strtotime($fechaEleccion)).", ";
$fijo5 .= " y una vez comprobado que las mismas correponden a las mesas electorales de ";
$fijo6 = " procedió de conformidad con lo establecido en el artículo 35 de las NORMAS PARA REGULAR LOS PROCESOS ELECTORALES DE GREMIOS Y COLEGIOS PROFESIONALES, ";
$fijo6 .= "a totalizar los votos registrados en las referidas actas, obteniéndose los siguientes resultados: ";
if($id_organismo < 4) {
	$ciudad = "Caracas, ";
	$comision = "Nacional de la Federación de Colegios de Contadores Públicos de la República Bolivariana de Venezuela, ";
	$mesasdequien = "los Colegios Federados (ver anexo), ";
//	"Comisión Electoral Nacional de la Federación de Colegios de Contadores Públicos de la República Bolivariana de Venezuela, ";
}
if($id_organismo > 3) {
	// obtener ciudad
	$sql = "SELECT ciudad  FROM centrosdevotacion a, ciudades b ";
	$sql .= "WHERE a.id_estado = $id_estado and a.isSede = 1 and a.id_ciudad = b.id ";
	if (!$resultado = $conx->query($sql)) {
		// La consulta falló. 
		echo "Lo sentimos, este sitio web está experimentando problemas. La consulta a la Tabla Totalizacion falló.";
		echo "Query: " . $sql . "\n";
		echo "Errno: " . $conx->errno . "\n";
		echo "Error: " . $conx->error . "\n";
		exit;
	}

	$ciudad_requery = $resultado->fetch_assoc();
	$ciudad = utf8_encode($ciudad_requery['ciudad']).', ';
	$comision = "Regional del Colegio de Contadores Públicos del ".($id_estado <> 24 ? 'Estado ' : '').$estado.", ";
	$mesasdequien = ($id_organismo == 4 ? 'este colegio' : 'este núcleo').' (ver anexo),';
	$resultado->free();
}
$pedazo1 = $fijo1.$ciudad.$fijo2.$comision.$fijo5.$mesasdequien.$fijo6;
require_once('fpdf/fpdf.php');
$V=new EnLetras();

class PDF extends FPDF
{
	var $P_hora;
	var $P_subtitulo;
	function changeName($hora) {
		$this->P_hora = $hora;
	}

	function subtitulo($subtitulo) {
		$this->P_subtitulo = $subtitulo;
	}

	function Footer()
	{
		// Pie de página
		// Posición: a 1,5 cm del final
		$this->SetY(-15);
		// Arial italic 8
		$this->SetFont('Arial','I',10);
		// Número de página
		$this->SetX(10);
		$this->Cell(20,10,'Fecha: '.date("d/m/Y"),0,0,'L');
		$this->Cell(150,10,utf8_decode('Página '.$this->PageNo().' de {nb}'),0,0,'C');
		$this->Cell(20,10,'Hora: '.strftime("%I:%M").$this->P_hora,0,0,'R');
		$this->SetLineWidth(0.6);
		$this->Line(10, 317, 200, 317);
		$this->SetLineWidth(0.2);
	}
	function enca_rep_nominal()
	{
		$this->SetFont('Arial','B',10);
		$this->SetX(10);
		$this->Cell(190,6,utf8_decode("REPRESENTACIÓN NOMINAL ").$this->P_subtitulo,0,1,'C');
		$this->SetFont('Arial','B',8);
		$this->SetX(10);
		$this->Cell(15,7,"PLANCHA",1,0,'C',1); 
		$this->SetX(25);
		$this->SetFont('Arial','B',9);
		$this->Cell(56,7,"CARGO NOMINAL",1,0,'C',1);
		$this->SetX(81);
		$this->SetFont('Arial','B',8);
		$this->Cell(58,4,"NOMBRES Y APELLIDOS",'TR',0,'C',1);
		$this->SetX(139);
		$this->Cell(11,4,"VOTOS",'TR',0,'C',1);
		$this->SetX(150);
		$this->Cell(50,4,"VOTOS",'TR',1,'C',1);
		$this->SetX(81);
		$this->SetFont('Arial','B',8);
		$this->Cell(58,3,"DEL CANDIDATO",'BR',0,'C',1);
		$this->SetX(139);
		$this->SetFont('Arial','',5);
		$this->Cell(11,3,utf8_decode("(en números)"),'BR',0,'C',1);
		$this->SetX(150);
		$this->SetFont('Arial','',8);
		$this->Cell(50,3,"(en letras)",'BR',1,'C',1);
	}
	function enca_rep_lista()
	{
		// Encabezado para Impresión de los totales por plancha por organo
		$this->Ln(2);
		$this->SetFont('Arial','B',9);
		$this->SetX(10);
		$this->Cell(190,6,utf8_decode("REPRESENTACIÓN LISTA ").$this->P_subtitulo,0,1,'C');
		$this->SetFont('Arial','B',9);
		$this->SetX(10);
		$this->Cell(80,5,"PLANCHA - ORGANO",1,0,'C',1); 
		$this->SetX(90);
		$this->Cell(50,5,utf8_decode("VOTOS (en números)"),1,0,'C',1);
		$this->SetX(140);
		$this->Cell(60,5,"VOTOS (en letras)",1,1,'C',1);	
	}
	function enca_proclamacion()
	{
		$this->SetFont('Arial','B',9);
		$this->SetX(10);
		$this->Cell(20,4,"PLANCHA",1,0,'C',1); 
		$this->SetX(30);
		$this->Cell(70,4,"CARGO",1,0,'C',1);
		$this->SetX(100);
		$this->SetFont('Arial','B',8);
		$this->Cell(75,4,"NOMBRE Y APELLIDO DEL PROCLAMADO",1,0,'C',1);
		$this->SetX(175);
		$this->SetFont('Arial','B',7);
		$this->Cell(25,4,utf8_decode("CÉDULA IDENTIDAD"),1,1,'C',1);
	}
}
	$pdf = new PDF('P','mm',array(210,330));
	$pdf->AliasNbPages();
	$pdf->AddPage();
	$pdf->changeName($hora);
	$pdf->SetFillColor(200,200,200);

	// FINAL Se sumarizan los votos nominales para el cargo y sus votos VÁLIDOS para cada candidato
	// Impresión de los totales por candidato por cargo Nominal
	$pdf->SetFont('Arial','B',12);
	$pdf->Image('logos/fccpv.png',2,1,-350);
	// Título
	$pdf->SetXY(15,7);
	if($cronograma == 3)
		$pdf->Cell(0,6,utf8_decode("ACTA DE TOTALIZACIÓN, ADJUDICACIÓN Y PROCLAMACIÓN"),0,1,'C');
	else
		$pdf->Cell(0,6,utf8_decode("RESULTADOS ELECTORALES PARCIALES"),0,1,'C');
	$pdf->Ln(5);
	$pdf->SetFont('Arial','B',10);
	if ($id_organismo < 4) $pdf->Cell(0,6,utf8_decode("FEDERACIÓN DE COLEGIOS DE CONTADORES PÚBLICOS DE LA REPÚBLICA BOLIVARIANA DE VENEZUELA"),0,1,'C');
	if ($id_organismo > 3) $pdf->Cell(0,5,utf8_decode("COLEGIO DE CONTADORES PÚBLICOS DEL ".($id_estado <> 24 ? 'ESTADO ' : '').$estado),0,1,'C');
	if ($id_organismo == 5) $pdf->Cell(0,5,$nucleo,0,1,'C');
	if ($id_organismo == 2) 
		$pdf->Cell(0,6,utf8_decode("INSTITUTO DE PREVISIÓN SOCIAL DEL CONTADOR PÚBLICO 'ALVARO RAMÓN ALVARADO' (INPRECONTAD)"),0,1,'C');
	if ($id_organismo == 3) 
		$pdf->Cell(0,6,utf8_decode("COMITÉ DEPORTIVO NACIONAL DE CONTADORES PÚBLICOS (CODENACOPU)"),0,1,'C');
	$pdf->Ln(2);
	if($cronograma == 3) {
		$pdf->SetFont('Arial','',9);
		$pdf->MultiCell(190,4,utf8_decode($pedazo1),0,'J');
		$pdf->Ln(2);
	}
	$subtitulo = ' ';
	$pdf->subtitulo($subtitulo);
	
// AQUÍ iba lo cortado
		require_once('imprime_cuerpo_acta_total.php');

	// si el estado tiene núcleos dependientes (Anzoátegui o Falcón), procede a imprimir dentro de esta acta los datos de los escrutinios de su (s) núcleo (s)
	if($id_organismo == 4 and hasDependiente == 1){
		require_once('total_nucleos_dep.php');
	}

$leyenda = "Acto seguido, la Comisión Electoral acordó remitir al Consejo Nacional Electoral el resultado de la Totalización, Adjudicación y Proclamación ";
$leyenda .= "de la Elección celebrada. Se levanta la presente Acta en cinco (05) originales para los representantes de las planchas participantes. ";
$pdf->Ln(2);
$pdf->MultiCell(190,4,utf8_decode($leyenda),0,'J');
$pdf->Ln(1);
if($id_organismo < 4) $comision = "COMISIÓN ELECTORAL NACIONAL";
if($id_organismo > 3) $comision = "COMISIÓN ELECTORAL REGIONAL";

// se imprimen los miembros de la mesa electoral
$pdf->SetFont('Arial','B',9);
$pdf->SetX(10);
$pdf->Cell(190,4,utf8_decode($comision),0,1,'C');
$pdf->SetX(10);
$pdf->SetFont('Arial','B',8);
$pdf->Cell(30,4,utf8_decode('CÉDULA IDENTIDAD'),1,0,'C',1);
$pdf->SetX(40);
$pdf->Cell(70,4,'APELLIDOS Y NOMBRES',1,0,'C',1);
$pdf->SetX(110);
$pdf->Cell(40,4,'CARGO',1,0,'C',1);
$pdf->SetX(150);
$pdf->Cell(50,4,'FIRMA',1,1,'C',1);

$condi_est = "";
if($id_organismo > 3) $condi_est = " and c.id_estado = $id_estado "; // condición para mostrar los miembros para organismo 4: Colegio
$sql = "SELECT cargo, c.persona as cedula, nombres, apellidos FROM autoridades c, personas b ";
$sql .= "WHERE c.id_eleccion = $elec and c.id_comision = $id_comision ".$condi_est." and c.persona = b.cedula";
if (!$resul_8 = $conx->query($sql)) {
    // La consulta falló. 
    echo "Lo sentimos, este sitio web está experimentando problemas. La consulta a la Tabla Totalizacion falló.";
    echo "Query: " . $sql . "\n";
    echo "Errno: " . $conx->errno . "\n";
    echo "Error: " . $conx->error . "\n";
    exit;
}

$pdf->SetFont('Arial','',9);
while($det = $resul_8->fetch_assoc())
	{
		$pdf->SetX(10);
		$pdf->Cell(30,5,number_format($det['cedula'],0,",","."),1,0,'C');
		$pdf->SetX(40);
		$pdf->Cell(70,5,$det['nombres'].' '.$det['apellidos'],1,0,'C');
		$pdf->SetX(110);
		$pdf->Cell(40,5,$det['cargo'],1,0,'C');
		$pdf->SetX(150);
		$pdf->Cell(50,5,' ',1,1,'C');
	}

	// se imprimen los testigos de la mesa electoral
$pdf->Ln(4);
$pdf->SetFont('Arial','B',9);
$pdf->SetX(10);
$pdf->Cell(190,4,'TESTIGOS ELECTORALES',0,1,'C');
$pdf->SetFont('Arial','B',8);
$pdf->SetX(10);
$pdf->Cell(30,4,utf8_decode('CÉDULA IDENTIDAD'),1,0,'C',1);
$pdf->SetX(40);
$pdf->Cell(70,4,'APELLIDOS Y NOMBRES',1,0,'C',1);
$pdf->SetX(110);
$pdf->Cell(40,4,utf8_decode('EN REPRESENTACIÓN'),1,0,'C',1);
$pdf->SetX(150);
$pdf->Cell(50,4,'FIRMA',1,1,'C',1);

$codi_testigo = "";
$tabla_testigo = ($id_organismo < 4 ? " " : ", centrosdevotacion d ");
if($id_organismo > 3) {
	$codi_testigo = " and a.id_centro = d.id and d.id_estado = $id_estado ";
}

$id_comision = ($id_organismo < 4 ? 1 : 2); // si el organismo es nacional toma 1 sino 2
$sql = "SELECT plancha, a.cedula, nombres, apellidos FROM testigos a, personas b, planchas c".$tabla_testigo;
$sql .= "WHERE a.id_eleccion = $elec and a.id_comision = $id_comision ".$codi_testigo." and a.cedula = b.cedula and a.id_plancha = c.id";
$resul = $conx->query($sql);
if (!$resul = $conx->query($sql)) {
    // La consulta falló. 
    echo "Lo sentimos, este sitio web está experimentando problemas. La consulta a la Tabla Totalizacion falló.";
    echo "Query: " . $sql . "\n";
    echo "Errno: " . $conx->errno . "\n";
    echo "Error: " . $conx->error . "\n";
    exit;
}

$pdf->SetFont('Arial','',9);
while($det = $resul->fetch_assoc())
{
	$pdf->SetX(10);
	$pdf->Cell(30,5,number_format($det['cedula'],0,",","."),1,0,'C');
	$pdf->SetX(40);
	$pdf->Cell(70,5,$det['nombres'].' '.$det['apellidos'],1,0,'C');
	$pdf->SetX(110);
	$pdf->Cell(40,5,$det['plancha'],1,0,'C');
	$pdf->SetX(150);
	$pdf->Cell(50,5,' ',1,1,'C');
}
$pdf->Output();
	
?>
</body>
</html>
