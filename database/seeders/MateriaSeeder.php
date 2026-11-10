<?php

namespace Database\Seeders;

use App\Models\Carrera;
use App\Models\Materia;
use Illuminate\Database\Seeder;

class MateriaSeeder extends Seeder
{
    public function run(): void
    {
        // carrera => lista de materias (en orden del plan de estudio, sin repetidas dentro de cada carrera)
        $catalogo = [

            'Administración de Empresas' => [
                'Español', 'Matemáticas', 'Sociología', 'Informática General', 'Filosofía',
                'Expresión Oral y Escrita', 'Precálculo', 'Administración I', 'Informática Aplicada a los Negocios', 'Desarrollo Humano Integral',
                'Contabilidad I', 'Estadística I', 'Administración II', 'Matemática Financiera', 'Mercadotecnia General',
                'Contabilidad II', 'Estadística II', 'Psicología Organizacional', 'Métodos y Técnicas de Investigación', 'Historia de Honduras',
                'Contabilidad Administrativa', 'Legislación Empresarial y de Instituciones Financieras', 'Gestión del Talento Humano y Endomarketing', 'Publicidad', 'Investigación de Mercados',
                'Contabilidad Gerencial', 'Microeconomía', 'Métodos Cuantitativos para la Toma de Decisiones', 'Electiva I (Arte o Deporte)', 'Ventas',
                'Administración Financiera I', 'Macroeconomía', 'Electiva II (Ciencias Naturales)', 'Administración de Operaciones', 'Marketing Digital',
                'Administración Financiera II', 'Responsabilidad Social Empresarial', 'Administración de Mercados Financieros', 'Finanzas y Negocios Internacionales', 'Gestión del Riesgo Empresarial',
                'Planeación Estratégica Empresarial', 'Electiva III (Ciencias Sociales)', 'Administración de las Finanzas Públicas', 'Administración de Empresas Turísticas', 'Gestión de la Calidad Total',
                'Formulación y Evaluación de Proyectos', 'Administración de Pequeñas y Medianas Empresas', 'Inteligencia de Negocios', 'Logística y Cadena de Suministros', 'Control Estadístico de la Calidad',
                'Taller de Emprendimiento I', 'Desarrollo de Habilidades Gerenciales', 'Transformación Digital de la Empresa', 'Ética Profesional', 'Planeación y Diseño de un Modelo de Calidad',
                'Taller de Emprendimiento II', 'Administración de Empresas Agroindustriales', 'Proyecto Integrador Universidad Empresa', 'Doctrina Social de la Iglesia', 'Sistema de Gestión Empresarial',
                'Práctica Profesional Supervisada',
            ],

            'Finanzas' => [
                'Español', 'Matemáticas', 'Sociología', 'Informática I', 'Filosofía',
                'Expresión Oral y Escrita', 'Precálculo', 'Contabilidad I', 'Administración I', 'El Hombre Frente a la Vida',
                'Estadística I', 'Historia de Honduras', 'Contabilidad II', 'Administración II', 'Informática II', 'Electiva I',
                'Estadística II', 'Contabilidad de Instituciones Financieras', 'Contabilidad Administrativa I', 'Mercadotecnia I', 'Administración de Recursos Humanos',
                'Métodos y Técnicas de Investigación', 'Matemática Financiera', 'Contabilidad Administrativa II', 'Mercadotecnia para Instituciones Financieras', 'Legislación Empresarial',
                'Microeconomía', 'Administración Financiera I', 'Presupuesto', 'Legislación de Instituciones Financieras', 'Relaciones de Trabajo en la Empresa', 'Electiva II',
                'Macroeconomía', 'Administración Financiera II', 'Fideicomiso', 'Crédito Comercial I', 'Estrategia Fiscal',
                'Economía Monetaria', 'Administración Financiera III', 'Administración de Sistemas Seguros', 'Crédito Comercial II', 'Comportamiento Humano y Desarrollo Organizacional',
                'Finanzas Internacionales', 'Formulación, Análisis e Interpretación Financiera', 'Administración de Inversiones', 'Ingeniería Financiera', 'Mercados de Capital', 'Electiva III',
                'Gestión de la Calidad Total', 'Administración de Instituciones Financieras', 'Auditoría de Instituciones Financieras', 'Formulación y Evaluación de Proyectos', 'Ciencia y Tecnología',
                'Control Estadístico de la Calidad', 'Finanzas Públicas', 'Administración Estratégica de Instituciones Financieras', 'Negociación Internacional y Desarrollo de Franquicias', 'Ética Profesional',
                'Planeación y Diseño de un Modelo de Calidad', 'Seminario de Finanzas', 'Métodos y Técnicas de Consultoría', 'Sistemas y Procesos en Finanzas', 'Doctrina Social de la Iglesia',
                'Práctica Profesional Supervisada',
            ],

            'Arquitectura' => [
                'Matemáticas', 'Español', 'Sociología', 'Dibujo I', 'Filosofía',
                'Precálculo', 'Expresión Oral y Escrita', 'Introducción al Diseño', 'Dibujo Técnico', 'El Hombre Frente a la Vida',
                'Cálculo I', 'Teoría de la Arquitectura I', 'Diseño Básico I', 'Expresión Gráfica I', 'Geometría Descriptiva I',
                'Física I', 'Teoría de la Arquitectura II', 'Diseño Básico II', 'Expresión Gráfica II', 'Geometría Descriptiva II',
                'Estática', 'Historia de Honduras', 'Diseño Básico III', 'Topografía', 'Ética Profesional',
                'Resistencia de Materiales', 'Materiales y Procedimientos Constructivos I', 'Electiva I (Ciencias Naturales, Técnico Científico)', 'Dibujo Aplicado a la Arquitectura', 'Historia de la Arquitectura I',
                'Análisis Estructural I', 'Materiales y Procedimientos Constructivos II', 'Diseño Intermedio I', 'Expresión Digital I', 'Historia de la Arquitectura II',
                'Análisis Estructural II', 'Instalación de Edificios I', 'Diseño Intermedio II', 'Expresión Digital II', 'Historia de la Arquitectura III',
                'Análisis Estructural III', 'Instalación de Edificios II', 'Diseño Intermedio III', 'Taller Constructivo I', 'Monumentos Religiosos',
                'Estadística I', 'Instalación de Edificios III', 'Electiva II (Arte, Cultura, Deporte)', 'Taller Constructivo II',
                'Gestión de la Calidad Total', 'Administración de Obras I', 'Taller Constructivo III', 'Doctrina Social de la Iglesia',
                'Control Estadístico de la Calidad', 'Administración de Obras II', 'Diseño Avanzado I', 'Urbanismo',
                'Planeación y Diseño de un Módulo de Calidad', 'Administración de Obras III', 'Diseño Avanzado II', 'Urbanismo y Ambiente I', 'Paisajismo',
                'Urbanismo y Ambiente II', 'Diseño Avanzado III', 'Arquitectura Bioclimática',
                'Seminario de Diseño Avanzado', 'Electiva III (Emprendimiento)',
                'Práctica Profesional Supervisada',
            ],

            'Ingeniería Civil' => [
                'Español', 'Sociología', 'Matemáticas', 'Dibujo I', 'Filosofía',
                'Expresión Oral y Escrita', 'Física I', 'Precálculo', 'Dibujo Técnico', 'El Hombre Frente a la Vida',
                'Estática', 'Dinámica', 'Cálculo I', 'Álgebra Lineal', 'Topografía',
                'Mecánica de Sólidos I', 'Física II', 'Cálculo II', 'Mecánica de Fluidos', 'Topografía Aplicada',
                'Materiales y Procesos de Construcción', 'Análisis Estructural I', 'Ecuaciones Diferenciales', 'Estadística I', 'Hidrología',
                'Métodos y Maquinaria de Construcción', 'Análisis Estructural II', 'Electiva I (Arte o Deporte)', 'Ingeniería Hidráulica y de Riesgos', 'Ética Profesional',
                'Mecánica de Sólidos II', 'Análisis Estructural III', 'Historia de Honduras', 'Mecánica de Suelos', 'Electiva II (Ciencias Naturales)',
                'Seminario de Investigación', 'Concreto', 'Geotecnia', 'Ingeniería Sanitaria I', 'Gestión de la Calidad Total',
                'Ingeniería Económica', 'Diseño de Estructuras de Concreto', 'Diseño de Cimentaciones', 'Diseño de Carreteras y Caminos', 'Control Estadístico de la Calidad',
                'Aspectos Legales de la Ingeniería Civil', 'Diseño de Pavimentos', 'Diseño de Estructuras Metálicas', 'Ingeniería Sanitaria II', 'Doctrina Social de la Iglesia',
                'Costos y Presupuesto de Obras', 'Electiva III (Institucionales)', 'Obras de Mitigación', 'Diseño de Instalaciones Eléctricas', 'Diseño de Obras Civiles Asistido por Computadora',
                'Formulación y Evaluación de Proyectos de Construcción', 'Diseño de Puentes', 'Taller de Diseño Combinado', 'Diseño de Urbanizaciones', 'Planeación y Diseño de un Modelo de Calidad',
                'Práctica Profesional Supervisada',
            ],

            'Ingeniería en Ciencias de la Computación' => [
                'Español', 'Matemáticas', 'Introducción a las Ciencias de la Computación', 'Sociología', 'Filosofía',
                'Expresión Oral y Escrita', 'Precálculo', 'Fundamentos y Lógica de Programación', 'Historia de Honduras', 'El Hombre Frente a la Vida',
                'Cálculo I', 'Programación Estructurada I', 'Estructuras Discretas', 'Electiva I (Ciencias Naturales)',
                'Métodos y Técnicas de Investigación', 'Cálculo II', 'Estadística I', 'Física I', 'Administración',
                'Base de Datos I', 'Contabilidad', 'Principios de Electrónica', 'Matemática Financiera', 'Programación en Entornos de Desarrollo Visual',
                'Redes I', 'Circuitos Lógicos', 'Ética Profesional', 'Programación Estructurada II', 'Análisis y Diseño de Sistemas',
                'Base de Datos II', 'Base de Datos Multidimensional', 'Programación Multiplataforma', 'Desarrollo de Software', 'Sistemas Automatizados',
                'Redes II', 'Electiva II (Arte o Deporte)', 'Sistemas Inteligentes para Negocios', 'Implementación de Sistemas de Software', 'Sistemas Operativos I', 'Microcontroladores',
                'Programación Móvil I', 'Desarrollo de Portales Web I', 'Gestión de la Calidad Total', 'Sistemas Operativos II', 'Seminario de Hardware y Electricidad',
                'Programación Móvil II', 'Desarrollo de Portales Web II', 'Control Estadístico de la Calidad', 'Gestión y Estándares de Tecnología de Información', 'Doctrina Social de la Iglesia',
                'Programación de Negocios', 'Negocios Web', 'Planeación y Diseño de un Modelo de Calidad', 'Seguridad Informática y Gestión del Riesgo', 'Administración de Centros de Cómputo',
                'Auditoría de Sistemas de Información', 'Electiva III (Especializante)', 'Gestión de Proyectos Informáticos', 'Seminario Taller de Software', 'Big Data',
            ],

            'Ingeniería Industrial' => [
                'Historia de Honduras', 'Matemáticas', 'Sociología', 'Español', 'Filosofía',
                'Electiva I (Institucionales)', 'Estadística I', 'Precálculo', 'Expresión Oral y Escrita', 'El Hombre Frente a la Vida',
                'Dibujo I', 'Estadística II', 'Cálculo I', 'Álgebra Lineal', 'Administración I',
                'Diseño Industrial Asistido por Computadora', 'Métodos y Técnicas de Investigación', 'Cálculo II', 'Física I', 'Química I',
                'Investigación de Operaciones I', 'Ecuaciones Diferenciales', 'Contabilidad I', 'Física II', 'Electiva II (Ciencias Naturales)',
                'Investigación de Operaciones II', 'Ingeniería de Métodos I', 'Producción Limpia y Energías Alternativas', 'Contabilidad de Costos para Ing. Industrial', 'Estática',
                'Administración de la Producción y Operaciones', 'Ingeniería de Métodos II', 'Instalaciones Eléctricas', 'Ética Profesional', 'Mecánica de Sólidos I',
                'Ergonomía', 'Algoritmos y Lenguajes de Programación', 'Mercadotecnia I', 'Gestión de Recursos Humanos', 'Ciencias de los Materiales',
                'Simulación de Procesos', 'Diseño de Planta y Manejo de Materiales', 'Mecánica de Fluidos Aplicada', 'Gestión de la Calidad Total', 'Administración Financiera',
                'Logística y Cadena de Suministro', 'Ingeniería en Sistemas de Producción I', 'Doctrina Social de la Iglesia', 'Control Estadístico de la Calidad', 'Ingeniería Económica',
                'Formulación y Evaluación de Proyectos', 'Ingeniería en Sistemas de Producción II', 'Sistemas Automatizados', 'Planeación y Diseño de un Modelo de Calidad', 'Legislación Empresarial',
                'Análisis y Estrategia de la Industria', 'Ingeniería en Sistemas de Mantenimiento Industrial', 'Seguridad e Higiene en el Trabajo', 'Aplicación de Sistemas Integrados de Calidad', 'Electiva III (Empresariales)',
                'Práctica Profesional Supervisada',
            ],

            'Derecho' => [
                'Matemáticas', 'Sociología', 'Introducción al Estudio del Derecho', 'Filosofía', 'Español',
                'Estadística I', 'Teoría General del Proceso', 'Derecho Romano', 'Teoría Política', 'Expresión Oral y Escrita',
                'Historia de Honduras', 'Derecho Penal I', 'Derecho Civil I', 'Teoría del Estado', 'Derecho del Trabajo y Seguridad Social I',
                'Derecho Procesal Civil I', 'Derecho Penal II', 'Derecho Civil II', 'Derecho Constitucional', 'Derecho del Trabajo y Seguridad Social II',
                'Derecho Procesal Civil II', 'Derechos de la Niñez y Mujer', 'Derecho Civil III', 'Derecho Administrativo I', 'El Hombre Frente a la Vida',
                'Psicología Aplicada a Derecho', 'Sociología del Derecho', 'Derecho Civil IV', 'Derecho Administrativo II', 'Informática I',
                'Métodos y Técnicas de Investigación', 'Lógica y Argumentación Jurídica', 'Derecho Civil V', 'Derecho Tributario', 'Informática Jurídica',
                'Legislación Penal Especial', 'Derecho Internacional Público', 'Derecho Mercantil I', 'Redacción y Oratoria Forense', 'Derechos Humanos',
                'Derecho Agrario', 'Derecho Notarial', 'Derecho Mercantil II', 'Derecho de la Propiedad Intelectual y Comunicaciones', 'Derecho Ambiental',
                'Derecho de Integración', 'Derecho Internacional Privado', 'Práctica Forense Civil', 'Práctica Forense Penal', 'Práctica Forense Laboral',
                'Gestión de la Calidad Total', 'Métodos y Técnicas de Investigación Jurídica', 'Derecho Mercantil Especial', 'Filosofía del Derecho', 'Doctrina Social de la Iglesia',
                'Control Estadístico de la Calidad', 'Derecho Comparado', 'Métodos Alternos de Solución de Controversias', 'Derecho Canónico', 'Ética Jurídica',
                'Planeación y Diseño de un Modelo de Calidad', 'Ciclo de Clínicas Procesales', 'Ordenamiento Territorial',
            ],

            'Enfermería' => [
                'Español', 'Matemáticas', 'Biología', 'Química', 'Filosofía',
                'Expresión Oral y Escrita', 'Historia de Honduras', 'Sociología', 'Fundamentos de Enfermería', 'El Hombre Frente a la Vida',
                'Bioestadística', 'Gestión de la Calidad Total', 'Histología para Enfermería', 'Introducción a la Salud Pública y Atención Primaria de la Salud', 'Ética Profesional',
                'Métodos y Técnicas de Investigación', 'Electiva I', 'Embriología para Enfermería', 'Prevención en Salud para Enfermería', 'Teorías y Modelos en Enfermería',
                'Anatomía General', 'Microbiología para Enfermería', 'Parasitología para Enfermería', 'Fisiología para Enfermería', 'Epidemiología',
                'Control Estadístico de la Calidad', 'Nutrición y Dietética', 'Fisiopatología para Enfermería', 'Semiología para Enfermería', 'Psicología Aplicada a la Enfermería',
                'Electiva II', 'Farmacología para Enfermería', 'Enfermería en Cuidados del Adulto', 'Enfermería en Cuidados Materno Infantil', 'Ética en Enfermería',
                'Enfermería Basada en Evidencia', 'Electiva III', 'Enfermería en Cuidados Gineco-Obstétricos', 'Enfermería Comunitaria', 'Administración de Servicios de Salud en Enfermería',
                'Investigación en Ciencias de la Salud', 'Enfermería en Cuidados del Niño y del Adolescente', 'Enfermería Médico Quirúrgica I', 'Salud Mental y Psicopatología', 'Gestión y Administración Hospitalaria',
                'Planeación y Diseño de un Modelo de Calidad', 'Enfermería Médico Quirúrgica II', 'Enfermería Ambulatoria', 'Doctrina Social de la Iglesia',
                'Diseño y Gestión de Proyectos en Salud', 'Enfermería en Rehabilitación y Salud Ocupacional', 'Enfermería Geriátrica', 'Enfermería en Emergencia y Cuidados Críticos', 'Enfermería en Cuidados Paliativo',
                'Internado Rotatorio en Enfermería Clínica (Ginecología y Obstetricia)', 'Internado Rotatorio en Enfermería Clínica (Pediatría)', 'Internado Rotatorio en Enfermería Clínica (Medicina Interna)',
                'Internado Rotatorio en Enfermería Psiquiátrica', 'Internado Rotatorio en Enfermería Comunitaria', 'Internado Rotatorio en Enfermería Clínica (Cirugía)',
                'Servicio Social',
            ],

            'Psicología' => [
                'Español', 'Biología', 'Matemáticas', 'Introducción a la Psicología', 'Filosofía',
                'Expresión Oral y Escrita', 'Sociología', 'Estadística I', 'Teorías de la Personalidad I', 'El Hombre Frente a la Vida',
                'Entrevista Psicológica', 'Psicofisiología I', 'Historia de Honduras', 'Electiva I', 'Ética Profesional',
                'Redacción Psicológica', 'Psicofisiología II', 'Métodos y Técnicas de Investigación', 'Psicología del Desarrollo del Niño', 'Ética en Psicología',
                'Electiva II', 'Métodos y Técnicas de Investigación Psicológica', 'Psicología del Desarrollo del Adolescente', 'Psicometría I', 'Teoría y Clínica del Psicoanálisis',
                'Psicología de las Organizaciones I', 'Psicología Social I', 'Psicología del Desarrollo de la Adultez y la Vejez', 'Psicometría II', 'Psicopatología I',
                'Teoría e Intervención en Psicología Humanística', 'Psicología Social II', 'Psicología del Aprendizaje', 'Psicometría III', 'Psicopatología II',
                'Teoría e Intervención en Psicología Cognitiva', 'Psicología Preventiva y Salud Pública', 'Psicología Educativa', 'Psicometría IV', 'Técnicas Proyectivas I',
                'Técnicas Terapéuticas I', 'Teoría y Abordaje de la Intervención en Crisis', 'Psicología de las Organizaciones II', 'Diagnóstico e Intervención Psico-Educativa', 'Técnicas Proyectivas II',
                'Técnicas Terapéuticas II', 'Orientación y Consejería Psicológica', 'Psicología del Talento Humano', 'Doctrina Social de la Iglesia', 'Diagnósticos e Informes Psicológicos',
                'Electiva III', 'Seminario de Investigación en Psicología Aplicada', 'Metodología y Modelos de Intervención Comunitaria', 'Orientación Vocacional y Profesional', 'Gestión de la Calidad Total',
                'Psicología Jurídica', 'Diseño y Desarrollo de Proyectos de Psicología Aplicada', 'Seminario de Salud Mental en Honduras', 'Psicología del Deporte', 'Control Estadístico de la Calidad',
                'Práctica Profesional Supervisada', 'Planeación y Diseño de un Modelo de Calidad',
            ],
        ];

        foreach ($catalogo as $nombreCarrera => $materias) {
            $carrera = Carrera::where('nombre', $nombreCarrera)->firstOrFail();

            foreach ($materias as $nombreMateria) {
                Materia::firstOrCreate([
                    'nombre' => $nombreMateria,
                    'carrera_id' => $carrera->id,
                ]);
            }
        }
    }
}
