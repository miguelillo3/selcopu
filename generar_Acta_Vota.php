!DOCTYPE html>
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
	$codigo_seguridad = $_REQUEST['mesa'];

// averigua la elección vigente
$sql = "SELECT id, status, periodo FROM elecciones WHERE status = 1";
if (!$resultado = $conx->query($sql)) {
    // La consulta falló. 
    echo "Lo sentimos, este sitio web está experimentando problemas.";
    echo "Error: La ejecución de la consulta falló debido a: \n";
    echo "Query: " . $sql . "\n";
    echo "Errno: " . $conx->errno . "\n";
    echo "Error: " . $conx->error . "\n";
    exit;
}
$eleccion = $resultado->fetch_assoc();
$stat = $eleccion['status'];
$elec = $eleccion['id'];
$periodo = "PERÍODO ELECTORAL ".$eleccion['periodo'];
$regs = $resultado->num_rows;
if ($regs == 0) {
    // No hay elección activa 
    echo "ERROR! No hay una Elección activa en estos momentos. Contacte a algún miembro de la Comisión Electoral Nacional.";
    exit;
}

$resultado->free();
// verifica la existencia del acta 
$sql = "SELECT id FROM mesas WHERE codigo_seguridad = '".$codigo_seguridad. "' and id_eleccion = $elec";
if (!$resultado = $conx->query($sql)) {
    // La consulta falló. 
    echo "ERROR: Problemas para acceder a la Tabla -mesas-. \n"." ".$sql;
    exit;
}
if ($resultado->num_rows == 0) {
    // La consulta falló. 
    echo "No existe ningún Acta con este dato: ".$codigo_seguridad." \n";
    exit;
}
$mesa_acta = $resultado->fetch_assoc();
$acta = $mesa_acta['id'];
$resultado->free();

// accede a la información cabecera del acta, junto a la de la mesa y del centro de votación
$sql = "SELECT b.mesa, b.codigo_seguridad, c.nombre, c.id, c.direccion, b.id_estado, b.id_ciudad, b.id_municipio, b.id_parroquia, ";
$sql .= "b.fechaHoraInicio, b.cedulaDesde, b.cedulaHasta, b.numElectoresInscritos FROM mesas b, centrosdevotacion c ";
$sql .= "WHERE b.id = $acta and b.id_centro = c.id";

//var_dump($periodo);
if (!$resultado = $conx->query($sql) or $resultado->num_rows == 0) {
    // La consulta falló. 
    echo "ERROR GRAVE! No existe la cabecera del Acta $acta. \n";
    exit;
}
$cabeza1 = $resultado->fetch_assoc();
$id_centro = $cabeza1['id'];
$id_estado = $cabeza1['id_estado'];
$id_ciudad = $cabeza1['id_ciudad'];
$id_municipio = $cabeza1['id_municipio'];
$id_parroquia = $cabeza1['id_parroquia'];
$direccion = $cabeza1['direccion'];
$nombre = $cabeza1['nombre'];

// obtiene el nombre del estado
$sql = "SELECT estado FROM estados WHERE id = $id_estado";
$resultado->free();
$resultado = $conx->query($sql);
$local1 = $resultado->fetch_assoc();
$nombre_estado = $local1['estado'];

// obtiene el nombre de la ciudad
$sql = "SELECT ciudad FROM ciudades WHERE id = $id_ciudad";
$resultado->free();
$resultado = $conx->query($sql);
$local2 = $resultado->fetch_assoc();

// obtiene el nombre del municipio
$sql = "SELECT municipio FROM municipios WHERE id = $id_municipio";
$resultado->free();
$resultado = $conx->query($sql);
$local3 = $resultado->fetch_assoc();

// obtiene el nombre de la parroquia
$sql = "SELECT parroquia FROM parroquias WHERE id = $id_parroquia";
$resultado->free();
$resultado = $conx->query($sql);
$local4 = $resultado->fetch_assoc();

$resultado->free();

// ************************************************************

$V=new EnLetras();

require_once('fpdf/fpdf.php');

	class PDF extends FPDF
	{
		var $P_periodo;
		var $P_copia;

		function changeName($periodo, $indice) 
		{
			 $this->P_periodo = $periodo;
			 $this->P_copia = $indice - 1;
		 }
		// Cabecera de página
		function Header()
		{
			// Logo
			$this->Image('logos/fccpv.png',2,4,-400);
			$this->Image('logos/cne.jpg',188,4,-400);
			$this->SetFont('Arial','',9);			
			// Título
			$this->SetY(5);
			$this->Cell(178,4,utf8_decode("FEDERACIÓN DE COLEGIOS DE CONTADORES PÚBLICOS DE LA REPÚBLICA BOLIVARIANA DE VENEZUELA"),0,1,'C');
			$this->Cell(178,4,utf8_decode("COMISIÓN ELECTORAL NACIONAL"),0,1,'C');
			$this->Cell(178,4,utf8_decode($this->P_periodo),0,1,'C');
			$this->SetFont('Arial','B',11);
			$this->Ln(1);
			$this->Cell(178,3,utf8_decode("ACTA DE VOTACIÓN"),0,1,'C');
			$this->SetFont('Arial','',9);			
			$this->Ln(2);
		}
		
		// Pie de página
		function Footer()
		{
			// Posición: a 1,5 cm del final
			$this->SetY(-15);
			$this->SetFont('Arial','I',11);
			// Número de página
			if($this->P_copia == 0) {
				$this->Cell(0,10,utf8_decode('ORIGINAL - Nota Importante: Rellenar sólo en tinta negra usando papel carbón para las copias'),0,0,'C');
			}
			if($this->P_copia > 0) {
				$this->Cell(0,10,'COPIA '.$this->P_copia.' de 4',0,0,'C');
			}
		}
	}
	
	// Creación del objeto de la clase heredada
	$pdf = new PDF('P','mm',array(210,340));
	$pdf->SetAutoPageBreak(1,20);
	$pdf->SetFillColor(200,200,200);
	
	$copias = 5; 

for($indice=1; $indice <= $copias; $indice++){

	$pdf->AddPage();
	$pdf->changeName($periodo, $indice);
	$pdf->SetLineWidth(0.5);
	$yy = $pdf->GetY();
	$pdf->Rect(5, $yy, 200, 30);
	$pdf->Ln(2);
	$pdf->SetFont('Arial','',7);
	$pdf->SetX(5);
	$pdf->Cell(30,3,utf8_decode('CENTRO DE VOTACIÓN: '),0,0,'L');
	$pdf->SetFont('Arial','B',10);
	$pdf->Cell(90,3,strtoupper($cabeza1['nombre']),0,0,'L');
	$pdf->SetFont('Arial','',7);
	$pdf->SetX(145);
	$pdf->Cell(12,3,utf8_decode('MESA N°: '),0,0,'L');
	$pdf->SetFont('Arial','B',10);
	$pdf->Cell(5,3,utf8_decode($cabeza1['mesa']),0,1,'L');
	$pdf->Ln(1);

	$pdf->SetFont('Arial','',7);
	$pdf->SetX(5);
	$pdf->Cell(45,3,utf8_decode('DIRECCIÓN CENTRO DE VOTACIÓN: '),0,0,'L');
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(98,3,strtoupper($cabeza1['direccion']),0,0,'L');
	$pdf->SetX(150);
	$pdf->SetFont('Arial','',7);
	$pdf->Cell(23,3,utf8_decode('CÓDIGO DE ACTA: '),0,0,'L');
	$pdf->SetFont('Arial','B',10);
	$pdf->Cell(27,3,$cabeza1['codigo_seguridad'],0,1,'L');
	$pdf->Ln(1);

	$pdf->SetFont('Arial','',7);
	$pdf->SetX(5);
	$pdf->Cell(12,3,'ESTADO: ',0,0,'L');
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(30,3,strtoupper($local1['estado']),0,0,'L');
	$pdf->SetFont('Arial','',7);
	$pdf->SetX(60);
	$pdf->Cell(12,3,'CIUDAD: ',0,0,'L');
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(50,3,strtoupper($local2['ciudad']),0,0,'L');
	$pdf->SetFont('Arial','',7);
	$pdf->SetX(125);
	$pdf->Cell(15,4,'MUNICIPIO: ',0,0,'L');
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(65,4,strtoupper($local3['municipio']),0,1,'L');

	$pdf->SetFont('Arial','',7);
	$pdf->SetX(5);
	$pdf->Cell(18,4,'PARROQUIA: ',0,0,'L');
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(70,4,strtoupper($local4['parroquia']),0,0,'L');
	$pdf->SetFont('Arial','',7);
	$pdf->SetX(140);
	$pdf->Cell(20,3,'FECHA Y HORA: ',0,0,'L');
	$pdf->SetFont('Arial','B',9);
	$pdf->Cell(5,3,$cabeza1['fechaHoraInicio'],0,1,'L');
	$pdf->Ln(2);

	$pdf->SetLineWidth(0.3);
	$yy = $pdf->GetY();
	$pdf->Line(5, $yy, 205, $yy);
	$pdf->Ln(2);

	$pdf->SetFont('Arial','',7);
	$pdf->SetX(5);
	$pdf->Cell(44,4,utf8_decode('CÉDULAS DE IDENTIDAD -> DESDE: '),0,0,'L');
	$pdf->SetFont('Arial','B',10);
	$pdf->Cell(10,4,number_format($cabeza1['cedulaDesde'],0,",","."),0,0,'L');
	$pdf->SetFont('Arial','',7);
	$pdf->SetX(80);
	$pdf->Cell(10,4,'HASTA: ',0,0,'L');
	$pdf->SetFont('Arial','B',10);
	$pdf->Cell(10,4,number_format($cabeza1['cedulaHasta'],0,",","."),0,1,'L');

	$pdf->SetFont('Arial','',7);
	$pdf->SetX(5);
	$pdf->Cell(37,4,utf8_decode('N° ELECTORES -> INSCRITOS: '),0,0,'L');
	$pdf->SetFont('Arial','B',10);
	$pdf->Cell(10,4,number_format($cabeza1['numElectoresInscritos'],0,",","."),0,0,'L');
	$pdf->SetFont('Arial','',10);
	$pdf->Cell(44,4,'('.$V->ValorEnLetras($cabeza1['numElectoresInscritos'],"").')',0,1,'L');
	$pdf->Ln(5);

	$literal_1 = "Se constitureron en el local que oportunamente le fue asignado a los miembros de las mesas, quienes se identificaron, ";
	$literal_1 .= "dándose inicio al Acto de Votación.";
	$pdf->SetFont('Arial','',9);
	$pdf->SetX(5);
	$pdf->MultiCell(200,4,utf8_decode($literal_1),1,'L');
	$pdf->Ln(3);

	// se imprimen los miembros de la mesa electoral
	$pdf->SetFont('Arial','B',10);
	$pdf->SetX(5);
	$pdf->Cell(200,4,utf8_decode('CONSTITUCIÓN DE LA MESA ELECTORAL (MIEMBROS DE MESA)'),0,1,'C');
	$pdf->SetFont('Arial','B',8);
	$pdf->SetX(5);
	$pdf->Cell(30,4,utf8_decode('CÉDULA IDENTIDAD'),1,0,'C',1);
	$pdf->SetX(35);
	$pdf->Cell(70,4,'APELLIDOS Y NOMBRES',1,0,'C',1);
	$pdf->SetX(105);
	$pdf->Cell(50,4,'CARGO',1,0,'C',1);
	$pdf->SetX(155);
	$pdf->Cell(50,4,'FIRMA',1,1,'C',1);
	$sql = "SELECT cargo, a.persona, nombres, apellidos FROM autoridades a, personas b ";
	$sql .= "WHERE a.id_eleccion = $elec and a.id_comision = 4 and a.id_mesa = $acta and a.instalo = 1 and a.persona = b.cedula";
	$resul = $conx->query($sql);
	$pdf->SetFont('Arial','',9);

	while($det = $resul->fetch_assoc())
	{
		$pdf->SetX(5);
		$pdf->Cell(30,5,number_format($det['persona'],0,",","."),1,0,'C');
		$pdf->SetX(35);
		$pdf->Cell(70,5,$det['nombres'].' '.$det['apellidos'],1,0,'C');
		$pdf->SetX(105);
		$pdf->Cell(50,5,$det['cargo'],1,0,'C');
		$pdf->SetX(155);
		$pdf->Cell(50,5,' ',1,1,'C');
	}
	
	$literal_1 = "Los Miembros de Mesa Electoral prestaron juramento de Ley para constituir Mesa Electoral en este Acto";
	$pdf->Ln(2);
	$pdf->SetFont('Arial','',11);
	$pdf->SetX(5);
	$pdf->Cell(200,5,utf8_decode($literal_1),0,1,'C');
	$pdf->Ln(1);

	$literal_1 = "Observaciones al Acto Electoral: Se reportan todas las incidencias del Acto de Votación. No se debe escribir en los cuadernos ";
	$literal_1 .= "Electorales, sólo está permitido plasmar las incidencias en este renglón del Acta de Votación";
	$pdf->SetLineWidth(0.5);
	$yy = $pdf->GetY();
	$yy2 = $yy + 2;
	$pdf->Rect(5, $yy, 200, 60);
	$pdf->Ln(1);
	$pdf->SetFont('Arial','',10);
	$pdf->SetX(5);
	$pdf->MultiCell(200,4,utf8_decode($literal_1),0,'C');
	$pdf->SetLineWidth(0.3);
	$yy = $pdf->GetY();
	for($i=0; $i<10; $i++){
		$yx = $yy + 5 * $i;
		$pdf->Line(5, $yx, 205, $yx);
	}
//	$yy = $pdf->GetY()+ 1;
//	$pdf->SetY($yy);
	$pdf->Ln(56);
	$literal_1 = "Una vez constatado el cumplimiento de los requisitos establecidos en la normativa electoral de la FEDERACIÓN DE COLEGIOS DE  ";
	$literal_1 .= "CONTADORES PÚBLICOS DE LA REPÚBLICA BOLIVARIANA DE VENEZUELA, se procedió a anunciar en alta y clara voz el inicio del Acto de Votación.";
	$pdf->SetX(5);
	$pdf->MultiCell(200,4,utf8_decode($literal_1),0,'C');
	$pdf->Ln(4);

	// se imprimen el formato de las desincorporaciones de los miembros de la mesa electoral
	$pdf->SetFont('Arial','B',10);
	$pdf->SetX(5);
	$pdf->Cell(200,4,utf8_decode('DESINCORPORACIÓN (indicar los datos de los Miembros de Mesa que se Desincorporan)'),0,1,'C');
	$pdf->Ln(1);
	$pdf->SetFont('Arial','B',8);
	$pdf->SetX(5);
	$pdf->Cell(30,4,utf8_decode('CÉDULA IDENTIDAD'),1,0,'C',1);
	$pdf->SetX(35);
	$pdf->Cell(70,4,'APELLIDOS Y NOMBRES',1,0,'C',1);
	$pdf->SetX(105);
	$pdf->Cell(40,4,'CARGO',1,0,'C',1);
	$pdf->SetX(145);
	$pdf->Cell(40,4,'FIRMA',1,0,'C',1);
	$pdf->Cell(20,4,'HORA',1,1,'C',1);
	$alto = 7;
	
	for($i=0; $i<3; $i++){
		$pdf->SetX(5);
		$pdf->Cell(30,$alto,' ',1,0);
		$pdf->SetX(35);
		$pdf->Cell(70,$alto,' ',1,0);
		$pdf->SetX(105);
		$pdf->Cell(40,$alto,' ',1,0);
		$pdf->SetX(145);
		$pdf->Cell(40,$alto,' ',1,0);
		$pdf->Cell(20,$alto,' ',1,1);
	}

	// se imprimen el formato de las incorporaciones de los miembros de la mesa electoral
	$pdf->Ln(3);
	$pdf->SetFont('Arial','B',10);
	$pdf->SetX(5);
	$pdf->Cell(200,4,utf8_decode('INCORPORACIÓN (indicar los datos de los Miembros de Mesa que se Incorporan)'),0,1,'C');
	$pdf->Ln(1);
	$pdf->SetFont('Arial','B',8);
	$pdf->SetX(5);
	$pdf->Cell(30,4,utf8_decode('CÉDULA IDENTIDAD'),1,0,'C',1);
	$pdf->SetX(35);
	$pdf->Cell(70,4,'APELLIDOS Y NOMBRES',1,0,'C',1);
	$pdf->SetX(105);
	$pdf->Cell(40,4,'CARGO',1,0,'C',1);
	$pdf->SetX(145);
	$pdf->Cell(40,4,'FIRMA',1,0,'C',1);
	$pdf->Cell(20,4,'HORA',1,1,'C',1);
	$alto = 7;
	
	for($i=0; $i<3; $i++){
		$pdf->SetX(5);
		$pdf->Cell(30,$alto,' ',1,0);
		$pdf->SetX(35);
		$pdf->Cell(70,$alto,' ',1,0);
		$pdf->SetX(105);
		$pdf->Cell(40,$alto,' ',1,0);
		$pdf->SetX(145);
		$pdf->Cell(40,$alto,' ',1,0);
		$pdf->Cell(20,$alto,' ',1,1);
	}

	// se imprime el formato del CIERRE DEL ACTO DE VOTACIÓN
	$pdf->Ln(3);
	$pdf->SetFont('Arial','B',10);
	$pdf->SetX(5);
	$pdf->Cell(200,4,utf8_decode('CIERRE DEL ACTO DE VOTACIÓN'),0,1,'C');
	$pdf->Ln(1);

	$literal_1 = "Siendo las _______________ y de conformidad con lo establecido en el Proyecto Electoral, se procede a anunciar en alta y clara voz el ";
	$literal_1 .= "cierre del Acto de Votación, y se declara formalmente terminado. La votación se realizó en la forma prevista en la normativa aplicable.";
	$pdf->SetLineWidth(0.5);
	$yy = $pdf->GetY();
	$pdf->Rect(5, $yy, 200, 31);
	$pdf->SetFont('Arial','',8);
	$pdf->Ln(3);
	$pdf->SetX(5);
	$pdf->MultiCell(200,6,utf8_decode($literal_1),0,'L');
	$pdf->Ln(3);
	$pdf->SetX(5);
	$pdf->Cell(120,5,utf8_decode('Total de Electores y Electoras que votaron según el Cuaderno de Votación (en números): _____________'),0,1);
	$pdf->Ln(1);
	$subrayado = '_______________________________________________________';
	$pdf->SetX(5);
	$pdf->Cell(180,5,utf8_decode('Total de Electores y Electoras que votaron según el Cuaderno de Votación (en letras): '.$subrayado),0,1,'L');

	// se imprime el formato para los miembros de la mesa electoral que cierran el acto de votación
	$pdf->SetLineWidth(0.3);
	$pdf->Ln(5);
	$pdf->SetFont('Arial','B',10);
	$pdf->SetX(5);
	$pdf->Cell(200,4,utf8_decode('MIEMBROS DE MESA ELECTORAL'),0,1,'C');
	$pdf->Ln(1);
	$pdf->SetFont('Arial','B',8);
	$pdf->SetX(5);
	$pdf->Cell(30,4,utf8_decode('CÉDULA IDENTIDAD'),1,0,'C',1);
	$pdf->SetX(35);
	$pdf->Cell(70,4,'APELLIDOS Y NOMBRES',1,0,'C',1);
	$pdf->SetX(105);
	$pdf->Cell(50,4,'CARGO',1,0,'C',1);
	$pdf->SetX(155);
	$pdf->Cell(50,4,'FIRMA',1,1,'C',1);

	$alto = 7;
	
	for($i=0; $i<3; $i++){
		$pdf->SetX(5);
		$pdf->Cell(30,$alto,' ',1,0);
		$pdf->SetX(35);
		$pdf->Cell(70,$alto,' ',1,0);
		$pdf->SetX(105);
		$pdf->Cell(50,$alto,' ',1,0);
		$pdf->SetX(155);
		$pdf->Cell(50,$alto,' ',1,1);
	}

} // FINAL de la iteracción de la cantidad de copias
	
	$pdf->Output();
	
	
// ************************************************************
//$conx->close();	
?>
</body>
</html>
