//---------------------------------------------
//ADJUNTOS UAFE
//---------------------------------------------
CREATE TABLE comercial.archivos_uafe (
id SERIAL PRIMARY KEY,
empr_cod_empr INTEGER NOT NULL,
titulo VARCHAR(200) NOT NULL,
ruta VARCHAR(500) NOT NULL,
estado VARCHAR(2) DEFAULT 'AC',
usuario_ingresa INTEGER NOT NULL,
fecha_ingresa TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
usuario_actualiza INTEGER,
fecha_actualiza TIMESTAMP
);

//---------------------------------------------
//TABLA EMPRESA
//---------------------------------------------
ALTER TABLE SAEEMPR ADD emmpr_uafe_cprov boolean;

//---------------------------------------------
//TABLA ADJUNTOS CLPV
//---------------------------------------------
ALTER TABLE comercial.adjuntos_clpv ADD COLUMN id_archivo_uafe INTEGER;   -- Documento UAFE al que pertenece
ALTER TABLE comercial.adjuntos_clpv ADD COLUMN fecha_entrega timestamp NULL;
ALTER TABLE comercial.adjuntos_clpv ADD COLUMN periodo_uafe SMALLINT; /*--- yano
ALTER TABLE comercial.adjuntos_clpv ADD COLUMN fecha_vencimiento_uafe DATE;

//---------------------------------------------
//TABLA TIPO PROVEEDOR
//---------------------------------------------
ALTER TABLE saetprov ADD COLUMN tprov_venc_uafe date;

- -----------PRUEBAS DOCUMENTACION UAFE ---------------
--VERIFICACION DE DOCUEMNTOS UAFE EN EL SISTEMA
SELECT empr_nom_empr, emmpr_uafe_cprov FROM saeempr
- -VERIFICACION DE LOS DOCUMENTACION UAFE A CUMPLIR
SELECT * FROM comercial.archivos_uafe
- -(1) VERIFICAMOS EL ESTADO DEL PROVEEDOR

SELECT clpv_cod_clpv, clpv_est_clpv, clpv_nom_clpv
FROM saeclpv
WHERE clpv_cod_clpv = 18046;

- -(2) VERIFICAMOS LA FECHA DE VENCIMIENTO DEL DOCUMENTO UAFE
SELECT * FROM saetprov
- -(3) VERIFICAMOS ESTADO DE DOCUMENTACION UAFE DEL PROVEEDOR
SELECT * FROM comercial.adjuntos_clpv

SELECT id_clpv, titulo, estado, id_archivo_uafe, fecha_entrega, periodo_uafe, fecha_vencimiento_uafe
FROM comercial.adjuntos_clpv
WHERE id_clpv = 18046

SELECT * FROM saeclpv
ALTER TABLE saeclpv
ADD COLUMN clpv_desc_actividades VARCHAR(255);