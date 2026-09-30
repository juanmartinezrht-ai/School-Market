const fs = require('fs');
const path = require('path');
const { Document, Packer, Paragraph, TextRun, HeadingLevel, AlignmentType, BorderStyle, Table, TableRow, TableCell, WidthType, ShadingType } = require('docx');

const doc = new Document({
    sections: [{
        properties: {},
        children: [
            // Title
            new Paragraph({
                text: "DISEÑO TÉCNICO Y ARQUITECTURA DE CÓDIGO",
                heading: HeadingLevel.TITLE,
                alignment: AlignmentType.CENTER,
                spacing: { after: 120 }
            }),
            new Paragraph({
                children: [
                    new TextRun({
                        text: "PLATAFORMA WEB: SCHOOL MARKET",
                        bold: true,
                        size: 32,
                        color: "4F46E5"
                    })
                ],
                alignment: AlignmentType.CENTER,
                spacing: { after: 300 }
            }),
            new Paragraph({
                children: [
                    new TextRun({ text: "Mercado Estudiantil de Emprendimiento, Economía Practica y Comercio Educativo", italic: true, size: 24, color: "6B7280" })
                ],
                alignment: AlignmentType.CENTER,
                spacing: { after: 400 }
            }),

            // Section 1: Introduction
            new Paragraph({
                text: "1. Descripción del Proyecto y Filosofía Pedagógica",
                heading: HeadingLevel.HEADING_1,
                spacing: { before: 300, after: 150 }
            }),
            new Paragraph({
                children: [
                    new TextRun({
                        text: "School Market es una plataforma web interactiva concebida para brindar a estudiantes de secundaria e instituciones educativas un espacio seguro, dinámico e intuitivo donde puedan publicar sus emprendimientos, comercializar productos o servicios hechos por ellos mismos (manualidades, tutorías, repostería, tecnología, libros, etc.) y aprender principios clave de economía, finanzas personales y gestión de negocios."
                    })
                ],
                spacing: { after: 200 }
            }),
            new Paragraph({
                children: [
                    new TextRun({ bold: true, text: "Objetivos Principales del Proyecto:" }),
                ],
                spacing: { after: 100 }
            }),
            new Paragraph({ text: "• Fomentar la cultura emprendedora desde la etapa escolar.", bullet: { level: 0 } }),
            new Paragraph({ text: "• Enseñar conceptos de economía real (oferta, demanda, costo de producción, margen de ganancia).", bullet: { level: 0 } }),
            new Paragraph({ text: "• Conectar estudiantes compradores y vendedores dentro de su propia comunidad educativa.", bullet: { level: 0 } }),
            new Paragraph({ text: "• Ofrecer una experiencia moderna, accesible e interactiva con estándares de calidad web actuales.", bullet: { level: 0 }, spacing: { after: 300 } }),

            // Section 2: Architecture & UX Flow
            new Paragraph({
                text: "2. Arquitectura de Experiencia de Usuario (UI/UX) y Flujo de Navegación",
                heading: HeadingLevel.HEADING_1,
                spacing: { before: 300, after: 150 }
            }),
            new Paragraph({
                children: [
                    new TextRun({ text: "El sistema está estructurado en dos pantallas principales con transiciones fluidas:" })
                ],
                spacing: { after: 150 }
            }),
            new Paragraph({
                children: [
                    new TextRun({ bold: true, text: "A. Módulo de Autenticación Guiada (Estilo Google / Gmail Auth):" })
                ],
                spacing: { after: 100 }
            }),
            new Paragraph({ text: "• Interfaz minimalista con tarjeta central, inspirada en los estándares de diseño de Google.", bullet: { level: 0 } }),
            new Paragraph({ text: "• Pantalla de Inicio de Sesión vs. Registro de Cuenta Nueva con selección de rol.", bullet: { level: 0 } }),
            new Paragraph({ text: "• Captura de datos esenciales: Nombre Completo, Correo Estudiantil y Contraseña.", bullet: { level: 0 } }),
            new Paragraph({ text: "• Selección de Perfil: Comprador (para explorar y apoyar) o Vendedor/Emprendedor (para publicar).", bullet: { level: 0 } }),
            new Paragraph({ text: "• Selección de Institución Educativa / Escuela perteneciente.", bullet: { level: 0 }, spacing: { after: 200 } }),

            new Paragraph({
                children: [
                    new TextRun({ bold: true, text: "B. Mercado Principal (Marketplace Estudiantil):" })
                ],
                spacing: { after: 100 }
            }),
            new Paragraph({ text: "• Header Superior Inteligente: Muestra la marca, buscador en tiempo real, bienvenida al usuario activo, su rol y escuela.", bullet: { level: 0 } }),
            new Paragraph({ text: "• Botón '+ Crear / Vender Producto': Habilitado para vendedores, abre un formulario flotante interactivo.", bullet: { level: 0 } }),
            new Paragraph({ text: "• Banner Educativo de Economía: Tarjeta interactiva con tips sobre cómo calcular precios y manejar ganancias.", bullet: { level: 0 } }),
            new Paragraph({ text: "• Filtros por Categorías: Gastronomía, Arte & Manualidades, Tutorías, Tecnología, Libros, Utiles, etc.", bullet: { level: 0 } }),
            new Paragraph({ text: "• Tarjetas de Producto Vibrantes: Muestra foto/icono, precio, etiqueta de la escuela, vendedor y calificación.", bullet: { level: 0 } }),
            new Paragraph({ text: "• Modal de Detalle de Producto & Vendedor: Al hacer clic en un objeto, se despliega la descripción completa, precio de producción, horario de entrega en el colegio y datos directos de contacto con el estudiante vendedor.", bullet: { level: 0 }, spacing: { after: 300 } }),

            // Section 3: Architecture for Future Database Integration
            new Paragraph({
                text: "3. Arquitectura y Preparación para Integración de Base de Datos (DB Ready)",
                heading: HeadingLevel.HEADING_1,
                spacing: { before: 300, after: 150 }
            }),
            new Paragraph({
                children: [
                    new TextRun({
                        text: "Para garantizar una transición limpia de una simulación de cliente a una aplicación conectada a un servidor real o servicio BaaS (Backend-as-a-Service), la aplicación implementa el patrón de diseño "
                    }),
                    new TextRun({ text: "Repository / Service Layer", bold: true }),
                    new TextRun({ text: "." })
                ],
                spacing: { after: 150 }
            }),
            new Paragraph({
                children: [
                    new TextRun({ bold: true, text: "Estructura del Módulo DataService (Services API):" })
                ],
                spacing: { after: 100 }
            }),
            new Paragraph({ text: "1. Encapsulación de Métodos Asíncronos (Promises/async-await): Aunque actualmente almacena en localStorage y memoria reactiva, todas las funciones retornan Promesas JS para simular latencia de red.", bullet: { level: 0 } }),
            new Paragraph({ text: "2. Puntos de Conexión Futuros:", bullet: { level: 0 } }),
            new Paragraph({ text: "   - Supabase / Firebase: Se pueden reemplazar los métodos Auth.login() y Products.getAll() introduciendo los SDKs oficial de Supabase o Firebase Firestore.", bullet: { level: 1 } }),
            new Paragraph({ text: "   - Backend Node.js / Express / REST API: Basta con cambiar las llamadas locales por `fetch('https://api.schoolmarket.edu/products')`.", bullet: { level: 1 } }),
            new Paragraph({ text: "3. Esquema de Datos Normalizado (Entities):", bullet: { level: 0 } }),
            new Paragraph({ text: "   - User: { id, name, email, role ['buyer' | 'seller'], school, createdAt }", bullet: { level: 1 } }),
            new Paragraph({ text: "   - Product: { id, title, price, category, description, sellerName, sellerGrade, sellerSchool, sellerContact, rating, image }", bullet: { level: 1 }, spacing: { after: 300 } }),

            // Section 4: File Structure Overview
            new Paragraph({
                text: "4. Estructura de Archivos del Código",
                heading: HeadingLevel.HEADING_1,
                spacing: { before: 300, after: 150 }
            }),
            new Paragraph({
                children: [
                    new TextRun({ text: "El proyecto se compone de 3 documentos principales completamente independientes y modulares:" })
                ],
                spacing: { after: 150 }
            }),
            new Paragraph({ text: "• index.html: Documento de marcado semántico HTML5.", bullet: { level: 0 } }),
            new Paragraph({ text: "• styles.css: Hoja de estilos con variables de color, responsive flex/grid y animaciones glassmorphism.", bullet: { level: 0 } }),
            new Paragraph({ text: "• app.js: Controlador JavaScript modular con manejo de estado, capa de servicio y modales.", bullet: { level: 0 }, spacing: { after: 300 } }),

            // Section 5: Code Documentation Overview
            new Paragraph({
                text: "5. Resumen de Implementación de Código Fuente",
                heading: HeadingLevel.HEADING_1,
                spacing: { before: 300, after: 150 }
            }),
            new Paragraph({
                children: [
                    new TextRun({ text: "Los archivos fuente generados en el directorio del proyecto ofrecen una experiencia de usuario de nivel profesional con las siguientes características técnicas clave:" })
                ],
                spacing: { after: 150 }
            }),
            new Paragraph({ text: "✓ Interfaz de autenticación con transiciones suaves entre Iniciar Sesión y Crear Cuenta.", bullet: { level: 0 } }),
            new Paragraph({ text: "✓ Catálogo de productos precargado con ejemplos reales de emprendimientos escolares (Tutorías de Matemáticas, Brownies caseros, Stickers de código, Dibujos personalizados, etc.).", bullet: { level: 0 } }),
            new Paragraph({ text: "✓ Búsqueda inteligente por nombre de producto y filtrado instantáneo por categorías.", bullet: { level: 0 } }),
            new Paragraph({ text: "✓ Modal de detalle que expone transparente y detalladamente la información del estudiante vendedor para concretar la compra en el colegio.", bullet: { level: 0 } }),
            new Paragraph({ text: "✓ Formulario de publicación con validaciones en tiempo real para nuevos emprendedores.", bullet: { level: 0 }, spacing: { after: 300 } })
        ]
    }]
});

Packer.toBuffer(doc).then((buffer) => {
    fs.writeFileSync(path.join(__dirname, "School_Market_Diseno_y_Codigo.docx"), buffer);
    console.log("Documento Word creado con éxito: School_Market_Diseno_y_Codigo.docx");
}).catch(err => {
    console.error("Error creando documento Word:", err);
});
