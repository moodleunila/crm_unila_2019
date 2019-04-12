CREATE OR REPLACE VIEW leads AS
SELECT DISTINCT mayusculaMinuscula(SUBSTRING_INDEX(SUBSTRING_INDEX(a.name, ' ', 1), ' ', -1)) AS nombre, mayusculaMinuscula (TRIM( SUBSTR(a.name, LOCATE(' ', a.name)) )) AS apellidos, email as correo,b.nombre AS nivel,origin as origen,TRIM(REPLACE(c.nombre, 'Campus','')) as campus,phone AS telefono,cellphone as celular, d.nombre AS carrera,date as fecha,hour as hora
FROM`registry` a,nivel_nivel b,campus_campus c, programa_programa d
WHERE a.nivel = b.id AND a.campus = c.id and a.program = d.id ORDER By a.id
