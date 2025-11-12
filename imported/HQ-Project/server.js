const express = require('express');
const cors = require('cors');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = 3000;

// Middleware
app.use(cors());
app.use(express.json());
app.use(express.static('.'));

// GET /questions - Load questions from JSON file
app.get('/questions', (req, res) => {
    const filePath = path.join(__dirname, 'data', 'questions.json');
    try {
        if (fs.existsSync(filePath)) {
            const data = fs.readFileSync(filePath, 'utf8');
            res.json(JSON.parse(data));
        } else {
            res.json({});
        }
    } catch (error) {
        console.error('Error reading questions file:', error);
        res.status(500).json({ error: 'Failed to load questions' });
    }
});

// POST /questions - Save questions to JSON file
app.post('/questions', (req, res) => {
    const data = req.body;
    const filePath = path.join(__dirname, 'data', 'questions.json');
    try {
        fs.writeFileSync(filePath, JSON.stringify(data, null, 2));
        res.json({ success: true });
    } catch (error) {
        console.error('Error saving questions file:', error);
        res.status(500).json({ error: 'Failed to save questions' });
    }
});

app.listen(PORT, () => {
    console.log(`Server running on http://localhost:${PORT}`);
});
