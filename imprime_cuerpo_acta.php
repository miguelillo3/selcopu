<?php
	// se recorre el vector de los cargos
	$total_validos = 0;
	$total_nulos = 0;
	
    $alto1 = 8;
	while($carg = $resul2->fetch_assoc())
	{
		$alto2 = ($carg['cant'] > 2 && $id_organismo == 1 ? 3 : 4);
		$pdf->SetFont('Arial','',8);
		$alto = $carg['cant'] * $alto2;
		$pdf->SetX(5);
		$pdf->Cell(50,$alto,$carg['cargo'],1,0,'C');
		$id_carg = $carg['id_cargo'];

		// INICIO obtención de los votos nulos para el cargo
		$sql = "SELECT votosNul FROM votosnulosnominales WHERE id_actaDeEscrutinios = $acta and id_cargoDeEleccion = $id_carg";
if (!$resul = $conx->query($sql)) {
    // La consulta falló. 
    echo "Error: La ejecución de la consulta falló debido a: \n";
    echo "Query: " . $sql . "\n";
    echo "Errno: " . $conx->errno . "\n";
    echo "Error: " . $conx->error . "\n";
    exit;
}
		$nulos = $resul->fetch_assoc();
		$pdf->SetX(178);
		$pdf->Cell(15,$alto,$V->ValorEnLetras($nulos['votosNul'],""),1,0,'C');
		$pdf->SetX(193);
		$pdf->Cell(12,$alto,$nulos['votosNul'],1,0,'C');
		$total_nulos += $nulos['votosNul'];
		// FINAL obtención de los votos nulos para el cargo

		// INICIO proceso para obtener los candidatos nominales para el cargo y sus votos VÁLIDOS
		$sql = "SELECT votosVal, b.id_cargo, cargo, e.nombres, e.apellidos, plancha ";
		$sql .= "FROM votosvalidosnominales a, postulaciones b, cargosdeeleccion c, candidatos d, personas e, planchas f ";
		$sql .= "WHERE id_actaDeEscrutinios = $acta and a.id_boleta = b.id and b.id_cargo = $id_carg and b.id_cargo = c.id and c.tipo_cargo = "."'Nominal'"." and ";
		$sql .= "      b.id_candidato = d.id and d.cedula = e.cedula and b.id_plancha = f.id ORDER BY b.id_cargo, plancha";
if (!$resul = $conx->query($sql)) {
    // La consulta falló. 
    echo "Lo sentimos, este sitio web está experimentando problemas.";
    echo "Error: La ejecución de la consulta falló debido a: \n";
    echo "Query: " . $sql . "\n";
    echo "Errno: " . $conx->errno . "\n";
    echo "Error: " . $conx->error . "\n";
    exit;
}
		// FINAL proceso para obtener los candidatos nominales a cada cargo y sus votos VÁLIDOS

		// se recorre la matriz de los candidatos nominales
		while($det = $resul->fetch_assoc())
		{
		$pdf->SetX(55);
		$pdf->Cell(68,$alto2,$det['nombres'].' '.$det['apellidos'],1,0,'C');
		$pdf->SetX(123);
		$pdf->SetFont('BOD_CR','',11);
		$pdf->Cell(43,$alto2,$V->ValorEnLetras($det['votosVal'],""),1,0,'C');
		$pdf->SetFont('Arial','',8);
		$pdf->Cell(12,$alto2,$det['votosVal'],1,1,'C');
		$total_validos += $det['votosVal'];
		}
	}

	$resul2->data_seek(0);
	$pdf->Ln(2);
	// se imprimen los totales de los votos validos y nulos
	$total_general_votos = $total_validos + $total_nulos;
	$pdf->SetFont('Arial','',7);
	$pdf->SetX(5);
	$pdf->Cell(35,$alto2,utf8_decode('TOTAL DE VOTOS VÁLIDOS'),1,0,'L');
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(12,$alto2,number_format($total_validos,0,",","."),1,0,'C');

	$pdf->SetFont('Arial','',7);
	$pdf->SetX(70);
	$pdf->Cell(35,$alto2,utf8_decode('TOTAL DE VOTOS NULOS'),1,0,'L');
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(12,$alto2,number_format($total_nulos,0,",","."),1,0,'C');

	$pdf->SetFont('Arial','',7);
	$pdf->SetX(135);
	$pdf->Cell(48,$alto2,utf8_decode('TOTAL DE VOTOS VÁLIDOS Y NULOS'),1,0,'L');
	$pdf->SetFont('Arial','B',8);
	$pdf->Cell(12,$alto2,number_format($total_general_votos,0,",","."),1,1,'C');
	
// INICIO proceso para imprimir los votos por plancha
// se obtienen los organos del organismo involucrado
	$sql = "SELECT id_organo, organo, COUNT(id_plancha) as nro_planchas ";
	$sql .= "FROM votosplanchas a, organos b WHERE id_actaDeEscrutinios = $acta and a.id_organo = b.id GROUP BY id_organo ORDER BY id_organo";
if (!$resul = $conx->query($sql)) {
    // La consulta falló. 
    echo "Error: La ejecución de la consulta falló debido a: \n";
    echo "Query: " . $sql . "\n";
    echo "Errno: " . $conx->errno . "\n";
    echo "Error: " . $conx->error . "\n";
    exit;
}
	
	// Salto de línea
	$pdf->Ln(4);
	$pdf->SetFont('Arial','B',12);
	$pdf->Cell(178,5,$enca5,0,1,'C');
	$pdf->enca_plancha();
	
	while($orgs = $resul->fetch_assoc())
	{
		// INICIO proceso para obtener los votos por plancha del organo siendo procesado
		$id_organo = $orgs['id_organo'];
		$sql = "SELECT votosVal, votosNul, c.plancha ";
		$sql .= "FROM votosplanchas a, planchas c ";
		$sql .= "WHERE id_actaDeEscrutinios = $acta and a.id_organo = $id_organo and a.id_plancha = c.id ORDER BY c.plancha";
if (!$resul3 = $conx->query($sql)) {
    // La consulta falló. 
    echo "Error: La ejecución de la consulta falló debido a: \n";
    echo "Query: " . $sql . "\n";
    echo "Errno: " . $conx->errno . "\n";
    echo "Error: " . $conx->error . "\n";
    exit;
}
		// FINAL proceso para obtener los votos por plancha del organo siendo procesado

		// se imprime el organo en cuestión
		$alto_organo = $orgs['nro_planchas'] * $alto2;
		$pdf->SetFont('Arial','B',8);
		$pdf->SetX(5);
		$pdf->Cell(40,$alto_organo,$orgs['organo'],1,0,'C');
		$pdf->SetFont('Arial','',8);
		$total_validos = 0;
		$total_nulos = 0;
		// se recorre la data obtenida
		while($det = $resul3->fetch_assoc())
		{
			$pdf->SetX(45);
			$pdf->Cell(30,$alto2,$det['plancha'],1,0,'C');
			$pdf->SetX(75);
			$pdf->SetFont('BOD_CR','',11);
			$pdf->Cell(43,$alto2,$V->ValorEnLetras($det['votosVal'],""),1,0,'C');
			$pdf->SetFont('Arial','',8);
			$pdf->Cell(12,$alto2,number_format($det['votosVal'],0,",","."),1,0,'C');
			$pdf->SetX(130);
			$pdf->Cell(28,$alto2,$V->ValorEnLetras($det['votosNul'],""),1,0,'C');
			$pdf->Cell(12,$alto2,$det['votosNul'],1,1,'C');
			$total_validos += $det['votosVal'];
			$total_nulos += $det['votosNul'];
		}
		$pdf->Ln(1);
		// se imprimen los totales de los votos validos y nulos
		$total_general_votos = $total_validos + $total_nulos;
		$pdf->SetFont('Arial','',7);
		$pdf->SetX(45);
		$pdf->Cell(48,$alto2,utf8_decode('TOTALES DE VOTOS PARA ESTE ÓRGANO '),0,0,'L');
		$pdf->SetX(104);
		$pdf->Cell(14,$alto2,utf8_decode('VÁLIDOS'),0,0,'L');
		$pdf->SetFont('Arial','B',8);
		$pdf->Cell(12,$alto2,number_format($total_validos,0,",","."),1,0,'C');

		$pdf->SetFont('Arial','',7);
		$pdf->SetX(130);
		$pdf->Cell(28,$alto2,'NULOS',0,0,'R');
		$pdf->SetFont('Arial','B',8);
		$pdf->Cell(12,$alto2,number_format($total_nulos,0,",","."),1,0,'C');

		$pdf->SetFont('Arial','',7);
		$pdf->SetX(170);
		$pdf->Cell(25,$alto2,utf8_decode('VÁLIDOS Y NULOS'),0,0,'L');
		$pdf->SetFont('Arial','B',8);
		$pdf->Cell(10,$alto2,number_format($total_general_votos,0,",","."),1,1,'C');
		$pdf->Ln(1);
	
	}
?>
