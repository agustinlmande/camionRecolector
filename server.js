const express = require('express');
const path = require('path');
const app = express();

// Middleware para parsear JSON en peticiones POST
app.use(express.json());

// Servir archivos estáticos (HTML, CSS, JS, imágenes)
app.use(express.static(__dirname));

// Ruta para servir archivos .json directamente
app.get('/*.json', (req, res) => {
    const filePath = path.join(__dirname, req.path);
    res.sendFile(filePath, err => {
        if (err) {
            res.status(404).json({ error: 'Archivo JSON no encontrado' });
        }
    });
});

// Ejemplo de ruta API
app.get('/api/camiones/:id?', (req, res) => {
    // Aquí puedes implementar lógica para obtener camiones
    const id = req.params.id;
    if (id) {
        res.json({ id, placa: 'ABC123', estado: 'activo' });
    } else {
        res.json([{ id: 1, placa: 'ABC123' }, { id: 2, placa: 'XYZ789' }]);
    }
});

// Ruta para login (simulada)
app.post('/login.php', (req, res) => {
    const { email, password } = req.body;
    // Aquí iría la lógica real de autenticación
    if (email === 'admin@demo.com' && password === '1234') {
        res.json({ success: true, mensaje: 'Login exitoso' });
    } else {
        res.status(401).json({ success: false, mensaje: 'Credenciales incorrectas' });
    }
});

// Ruta por defecto para archivos no encontrados
app.use((req, res) => {
    res.status(404).send('Página no encontrada');
});

// Iniciar el servidor
const PORT = 3000;
app.listen(PORT, () => {
    console.log(`Servidor escuchando en http://localhost:${PORT}