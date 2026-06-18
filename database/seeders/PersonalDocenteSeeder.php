<?php
// database/seeders/PersonalDocenteSeeder.php

namespace Database\Seeders;

use App\Models\Personal;
use Illuminate\Database\Seeder;

class PersonalDocenteSeeder extends Seeder
{
    public function run(): void
    {
        $docentes = [
            // ── Docentes originales ──────────────────────────────────────────
            [
                'dui' => '01234567-8', 'nombres' => 'María Elena',
                'apellidos' => 'Gutiérrez Ramos', 'fecha_nacimiento' => '1982-03-15',
                'genero' => 'F', 'telefono' => '7210-4455',
                'correo' => 'mgutierrez@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Lenguaje y Literatura', 'fecha_ingreso' => '2010-01-08', 'activo' => true,
            ],
            [
                'dui' => '02345678-9', 'nombres' => 'José Antonio',
                'apellidos' => 'Hernández Mejía', 'fecha_nacimiento' => '1979-07-22',
                'genero' => 'M', 'telefono' => '7321-6677',
                'correo' => 'jhernandez@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Matemática', 'fecha_ingreso' => '2008-02-01', 'activo' => true,
            ],
            [
                'dui' => '03456789-0', 'nombres' => 'Ana Cecilia',
                'apellidos' => 'Martínez de López', 'fecha_nacimiento' => '1985-11-03',
                'genero' => 'F', 'telefono' => '7432-8899',
                'correo' => 'amartinez@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Ciencias Naturales', 'fecha_ingreso' => '2012-01-09', 'activo' => true,
            ],
            [
                'dui' => '04567890-1', 'nombres' => 'Carlos Roberto',
                'apellidos' => 'Flores Castillo', 'fecha_nacimiento' => '1980-05-18',
                'genero' => 'M', 'telefono' => '7543-1122',
                'correo' => 'cflores@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Estudios Sociales', 'fecha_ingreso' => '2007-01-15', 'activo' => true,
            ],
            [
                'dui' => '05678901-2', 'nombres' => 'Sandra Marisol',
                'apellidos' => 'Portillo Vásquez', 'fecha_nacimiento' => '1988-09-27',
                'genero' => 'F', 'telefono' => '7654-3344',
                'correo' => 'sportillo@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Inglés', 'fecha_ingreso' => '2015-01-12', 'activo' => true,
            ],
            [
                'dui' => '06789012-3', 'nombres' => 'Miguel Ángel',
                'apellidos' => 'Orellana Chávez', 'fecha_nacimiento' => '1975-02-14',
                'genero' => 'M', 'telefono' => '7765-5566',
                'correo' => 'morellana@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Informática', 'fecha_ingreso' => '2005-01-10', 'activo' => true,
            ],
            [
                'dui' => '07890123-4', 'nombres' => 'Karla Beatriz',
                'apellidos' => 'Ramírez Aguilar', 'fecha_nacimiento' => '1990-06-08',
                'genero' => 'F', 'telefono' => '7876-7788',
                'correo' => 'kramirez@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Educación Artística', 'fecha_ingreso' => '2016-01-11', 'activo' => true,
            ],
            [
                'dui' => '08901234-5', 'nombres' => 'Ernesto David',
                'apellidos' => 'Montes Granados', 'fecha_nacimiento' => '1983-12-30',
                'genero' => 'M', 'telefono' => '7987-9900',
                'correo' => 'emontes@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Educación Física', 'fecha_ingreso' => '2011-01-07', 'activo' => true,
            ],
            [
                'dui' => '09012345-6', 'nombres' => 'Rosa Amalia',
                'apellidos' => 'Peñate de García', 'fecha_nacimiento' => '1977-04-19',
                'genero' => 'F', 'telefono' => '7198-2233',
                'correo' => 'rpenate@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Moral, Urbanidad y Cívica', 'fecha_ingreso' => '2003-01-06', 'activo' => true,
            ],
            [
                'dui' => '10123456-7', 'nombres' => 'Héctor Manuel',
                'apellidos' => 'Campos Reyes', 'fecha_nacimiento' => '1986-08-25',
                'genero' => 'M', 'telefono' => '7209-4455',
                'correo' => 'hcampos@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Matemática', 'fecha_ingreso' => '2013-01-14', 'activo' => true,
            ],
            [
                'dui' => '11234567-8', 'nombres' => 'Lucía Gabriela',
                'apellidos' => 'Sorto Henríquez', 'fecha_nacimiento' => '1992-01-11',
                'genero' => 'F', 'telefono' => '7310-6677',
                'correo' => 'lsorto@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Lenguaje y Literatura', 'fecha_ingreso' => '2018-01-08', 'activo' => true,
            ],
            [
                'dui' => '12345678-9', 'nombres' => 'Edwin Alexander',
                'apellidos' => 'Rivas Mendoza', 'fecha_nacimiento' => '1981-10-05',
                'genero' => 'M', 'telefono' => '7421-8899',
                'correo' => 'erivas@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Ciencias Naturales', 'fecha_ingreso' => '2009-01-12', 'activo' => true,
            ],
            [
                'dui' => '13456789-0', 'nombres' => 'Patricia Verónica',
                'apellidos' => 'Luna de Méndez', 'fecha_nacimiento' => '1984-03-22',
                'genero' => 'F', 'telefono' => '7532-1122',
                'correo' => 'pluna@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Inglés', 'fecha_ingreso' => '2014-01-13', 'activo' => true,
            ],
            [
                'dui' => '14567890-1', 'nombres' => 'Oscar Guillermo',
                'apellidos' => 'Argueta Molina', 'fecha_nacimiento' => '1978-06-16',
                'genero' => 'M', 'telefono' => '7643-3344',
                'correo' => 'oargueta@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Estudios Sociales', 'fecha_ingreso' => '2006-01-09', 'activo' => true,
            ],
            [
                'dui' => '15678901-2', 'nombres' => 'Fátima Isabel',
                'apellidos' => 'Zelaya Cortez', 'fecha_nacimiento' => '1993-09-02',
                'genero' => 'F', 'telefono' => '7754-5566',
                'correo' => 'fzelaya@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Informática', 'fecha_ingreso' => '2020-01-06', 'activo' => true,
            ],

            // ── Docentes nuevos ──────────────────────────────────────────────

            // Lenguaje — secciones C/D de 1°–3° y las 4 secciones de 7°–9°
            [
                'dui' => '20000001-1', 'nombres' => 'Gloria Estela',
                'apellidos' => 'Bonilla Interiano', 'fecha_nacimiento' => '1983-05-10',
                'genero' => 'F', 'telefono' => '7111-0001',
                'correo' => 'gbonilla@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Lenguaje y Literatura', 'fecha_ingreso' => '2011-01-10', 'activo' => true,
            ],
            [
                'dui' => '20000002-2', 'nombres' => 'Roberto Carlos',
                'apellidos' => 'Leiva Escobar', 'fecha_nacimiento' => '1980-09-18',
                'genero' => 'M', 'telefono' => '7111-0002',
                'correo' => 'rleiva@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Lenguaje y Literatura', 'fecha_ingreso' => '2009-01-12', 'activo' => true,
            ],
            [
                'dui' => '20000003-3', 'nombres' => 'Silvia Lorena',
                'apellidos' => 'Aguilar Pineda', 'fecha_nacimiento' => '1987-02-25',
                'genero' => 'F', 'telefono' => '7111-0003',
                'correo' => 'saguilar@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Lenguaje y Literatura', 'fecha_ingreso' => '2014-01-07', 'activo' => true,
            ],

            // Lenguaje — secciones C/D de 4°–6°
            [
                'dui' => '20000004-4', 'nombres' => 'Diana Marcela',
                'apellidos' => 'Guardado Mira', 'fecha_nacimiento' => '1985-07-14',
                'genero' => 'F', 'telefono' => '7111-0004',
                'correo' => 'dguardado@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Lenguaje y Literatura', 'fecha_ingreso' => '2013-01-09', 'activo' => true,
            ],

            // Matemática — secciones C/D de 1°–3° y las 4 de 7°–9°
            [
                'dui' => '20000005-5', 'nombres' => 'Julio César',
                'apellidos' => 'Martínez Zepeda', 'fecha_nacimiento' => '1981-11-03',
                'genero' => 'M', 'telefono' => '7111-0005',
                'correo' => 'jmartinez@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Matemática', 'fecha_ingreso' => '2010-01-11', 'activo' => true,
            ],
            [
                'dui' => '20000006-6', 'nombres' => 'Mirna Yolanda',
                'apellidos' => 'Coreas Alvarado', 'fecha_nacimiento' => '1984-04-20',
                'genero' => 'F', 'telefono' => '7111-0006',
                'correo' => 'mcoreas@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Matemática', 'fecha_ingreso' => '2012-01-09', 'activo' => true,
            ],
            [
                'dui' => '20000007-7', 'nombres' => 'Fernando José',
                'apellidos' => 'Ventura Quijano', 'fecha_nacimiento' => '1979-08-30',
                'genero' => 'M', 'telefono' => '7111-0007',
                'correo' => 'fventura@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Matemática', 'fecha_ingreso' => '2008-01-14', 'activo' => true,
            ],

            // Matemática — secciones C/D de 4°–6°
            [
                'dui' => '20000008-8', 'nombres' => 'Blanca Estela',
                'apellidos' => 'Recinos Dueñas', 'fecha_nacimiento' => '1986-01-17',
                'genero' => 'F', 'telefono' => '7111-0008',
                'correo' => 'brecinos@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Matemática', 'fecha_ingreso' => '2015-01-12', 'activo' => true,
            ],

            // Ciencias Naturales — secciones C/D de 1°–3° y las 4 de 7°–9°
            [
                'dui' => '20000009-9', 'nombres' => 'Álvaro Enrique',
                'apellidos' => 'Serrano Bonilla', 'fecha_nacimiento' => '1982-06-05',
                'genero' => 'M', 'telefono' => '7111-0009',
                'correo' => 'aserrano@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Ciencias Naturales', 'fecha_ingreso' => '2011-01-10', 'activo' => true,
            ],
            [
                'dui' => '20000010-0', 'nombres' => 'Rebeca Liseth',
                'apellidos' => 'Iraheta Morales', 'fecha_nacimiento' => '1989-03-12',
                'genero' => 'F', 'telefono' => '7111-0010',
                'correo' => 'riraheta@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Ciencias Naturales', 'fecha_ingreso' => '2017-01-09', 'activo' => true,
            ],
            [
                'dui' => '20000011-1', 'nombres' => 'Nelson Geovanni',
                'apellidos' => 'Ayala Ramos', 'fecha_nacimiento' => '1978-10-22',
                'genero' => 'M', 'telefono' => '7111-0011',
                'correo' => 'nayala@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Ciencias Naturales', 'fecha_ingreso' => '2006-01-16', 'activo' => true,
            ],

            // Ciencias Naturales — secciones C/D de 4°–6°
            [
                'dui' => '20000012-2', 'nombres' => 'Claudia Beatriz',
                'apellidos' => 'Fuentes Navarrete', 'fecha_nacimiento' => '1985-12-01',
                'genero' => 'F', 'telefono' => '7111-0012',
                'correo' => 'cfuentes@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Ciencias Naturales', 'fecha_ingreso' => '2014-01-13', 'activo' => true,
            ],

            // Estudios Sociales — secciones C/D de 1°–3° y las 4 de 7°–9°
            [
                'dui' => '20000013-3', 'nombres' => 'Mauricio Alfredo',
                'apellidos' => 'Sánchez Portillo', 'fecha_nacimiento' => '1980-07-07',
                'genero' => 'M', 'telefono' => '7111-0013',
                'correo' => 'msanchez@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Estudios Sociales', 'fecha_ingreso' => '2009-01-12', 'activo' => true,
            ],
            [
                'dui' => '20000014-4', 'nombres' => 'Verónica Alejandra',
                'apellidos' => 'Chávez Blanco', 'fecha_nacimiento' => '1987-04-15',
                'genero' => 'F', 'telefono' => '7111-0014',
                'correo' => 'vchavez@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Estudios Sociales', 'fecha_ingreso' => '2016-01-11', 'activo' => true,
            ],
            [
                'dui' => '20000015-5', 'nombres' => 'Jaime Ernesto',
                'apellidos' => 'Villacorta Cea', 'fecha_nacimiento' => '1977-11-28',
                'genero' => 'M', 'telefono' => '7111-0015',
                'correo' => 'jvillacorta@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Estudios Sociales', 'fecha_ingreso' => '2004-01-08', 'activo' => true,
            ],

            // Estudios Sociales — secciones C/D de 4°–6°
            [
                'dui' => '20000016-6', 'nombres' => 'Isabel Cristina',
                'apellidos' => 'Orantes Lemus', 'fecha_nacimiento' => '1983-09-09',
                'genero' => 'F', 'telefono' => '7111-0016',
                'correo' => 'iorantes@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Estudios Sociales', 'fecha_ingreso' => '2012-01-10', 'activo' => true,
            ],

            // Inglés — secciones C/D en 7°–8° y las 4 secciones en 9°
            [
                'dui' => '20000017-7', 'nombres' => 'Katherine Denisse',
                'apellidos' => 'Molina Estrada', 'fecha_nacimiento' => '1991-02-18',
                'genero' => 'F', 'telefono' => '7111-0017',
                'correo' => 'kmolina@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Inglés', 'fecha_ingreso' => '2019-01-07', 'activo' => true,
            ],
            [
                'dui' => '20000018-8', 'nombres' => 'Erick Josué',
                'apellidos' => 'Alfaro Henríquez', 'fecha_nacimiento' => '1988-06-24',
                'genero' => 'M', 'telefono' => '7111-0018',
                'correo' => 'ealfaro@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Inglés', 'fecha_ingreso' => '2017-01-09', 'activo' => true,
            ],

            // Informática — secciones C/D en 7°–8° y las 4 secciones en 9°
            [
                'dui' => '20000019-9', 'nombres' => 'Darwin Alexis',
                'apellidos' => 'Melara Guevara', 'fecha_nacimiento' => '1990-01-30',
                'genero' => 'M', 'telefono' => '7111-0019',
                'correo' => 'dmelara@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Informática', 'fecha_ingreso' => '2018-01-08', 'activo' => true,
            ],
            [
                'dui' => '20000020-0', 'nombres' => 'Lorena Vanessa',
                'apellidos' => 'Cisneros Parada', 'fecha_nacimiento' => '1992-08-11',
                'genero' => 'F', 'telefono' => '7111-0020',
                'correo' => 'lcisneros@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Informática', 'fecha_ingreso' => '2021-01-10', 'activo' => true,
            ],

            // Educación Artística — secciones B/C/D de todos los grados básica
            [
                'dui' => '20000021-1', 'nombres' => 'Ana Sofía',
                'apellidos' => 'Parada Montoya', 'fecha_nacimiento' => '1989-05-20',
                'genero' => 'F', 'telefono' => '7111-0021',
                'correo' => 'aparada@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Educación Artística', 'fecha_ingreso' => '2019-01-07', 'activo' => true,
            ],
            [
                'dui' => '20000022-2', 'nombres' => 'Luis Rodrigo',
                'apellidos' => 'Tobar Galdámez', 'fecha_nacimiento' => '1984-11-11',
                'genero' => 'M', 'telefono' => '7111-0022',
                'correo' => 'ltobar@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Educación Artística', 'fecha_ingreso' => '2013-01-14', 'activo' => true,
            ],

            // Educación Física — secciones B/C/D de todos los grados básica
            [
                'dui' => '20000023-3', 'nombres' => 'Wilfredo Obed',
                'apellidos' => 'Mejía Castellanos', 'fecha_nacimiento' => '1981-03-07',
                'genero' => 'M', 'telefono' => '7111-0023',
                'correo' => 'wmejia@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Educación Física', 'fecha_ingreso' => '2010-01-11', 'activo' => true,
            ],
            [
                'dui' => '20000024-4', 'nombres' => 'Claudia Marisela',
                'apellidos' => 'Velásquez Torres', 'fecha_nacimiento' => '1986-07-19',
                'genero' => 'F', 'telefono' => '7111-0024',
                'correo' => 'cvelasquez@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Educación Física', 'fecha_ingreso' => '2015-01-12', 'activo' => true,
            ],

            // Moral, Urbanidad y Cívica — secciones B/C/D de 4°–9°
            [
                'dui' => '20000025-5', 'nombres' => 'Nora Elizabeth',
                'apellidos' => 'Cabrera Menjívar', 'fecha_nacimiento' => '1979-12-05',
                'genero' => 'F', 'telefono' => '7111-0025',
                'correo' => 'ncabrera@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Moral, Urbanidad y Cívica', 'fecha_ingreso' => '2007-01-15', 'activo' => true,
            ],
            [
                'dui' => '20000026-6', 'nombres' => 'Rodrigo Antonio',
                'apellidos' => 'Flores Meléndez', 'fecha_nacimiento' => '1983-08-23',
                'genero' => 'M', 'telefono' => '7111-0026',
                'correo' => 'rflores@cepja.edu.sv', 'tipo' => 'docente',
                'especialidad' => 'Moral, Urbanidad y Cívica', 'fecha_ingreso' => '2012-01-09', 'activo' => true,
            ],
        ];

        foreach ($docentes as $datos) {
            Personal::firstOrCreate(['dui' => $datos['dui']], $datos);
        }

        $this->command->info('✅ Docentes creados correctamente.');
    }
}
