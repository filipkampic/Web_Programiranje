const express = require('express');
const fs = require('fs');
const path = require('path');

const app = express();
const PORT = process.env.PORT || 3000; // process.env.PORT je obavezan za Railway

// Postavljanje EJS kao template enginea (Zadatak 3)
app.set('view engine', 'ejs');
app.set('views', path.join(__dirname, 'views'));

// Posluživanje statičkih datoteka iz mape public (Zadatak 1)
app.use(express.static(path.join(__dirname, 'public')));

// Ruta za početnu stranicu - poslužuje index.html iz public/ mape (Zadatak 1)
// express.static automatski pronalazi index.html, ali dodajemo i eksplicitnu rutu
app.get('/', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'index.html'));
});

// Ruta za graphs stranicu
app.get('/graphs', (req, res) => {
    res.sendFile(path.join(__dirname, 'public', 'pages', 'graphs.html'));
});

// GALERIJA - dinamičko generiranje putem EJS predloška (Zadatak 3)
// Ruta /gallery čita slike iz public/img/gallery/ mape i šalje ih EJS predlošku
app.get('/gallery', (req, res) => {
    const folderPath = path.join(__dirname, 'public', 'img', 'gallery');

    // Provjera postoji li mapa s galerijom
    if (!fs.existsSync(folderPath)) {
        return res.status(404).send('Mapa s galerijom nije pronađena.');
    }

    const files = fs.readdirSync(folderPath);

    // Filtriramo samo slike (.jpg, .jpeg, .png, .webp, .svg)
    const images = files
        .filter(file =>
            file.endsWith('.jpg') ||
            file.endsWith('.jpeg') ||
            file.endsWith('.png') ||
            file.endsWith('.webp') ||
            file.endsWith('.svg')
        )
        .map((file, index) => ({
            url: `/img/gallery/${file}`,           // putanja dostupna klijentu
            id: `slika${index + 1}`,               // jedinstveni id za lightbox
            title: formatTitle(file),              // naslov formatiran iz naziva datoteke
            alt: formatTitle(file)                 // alt tekst za pristupačnost
        }));

    // Renderiramo EJS predložak i prosljeđujemo podatke o slikama
    res.render('gallery', { images });
});

// Pomoćna funkcija: pretvara naziv datoteke u čitljiv naslov
// Npr. "foo-fighters.jpg" -> "Foo Fighters"
function formatTitle(filename) {
    return filename
        .replace(/\.[^.]+$/, '')         // ukloni ekstenziju
        .replace(/[-_]/g, ' ')           // zamijeni crtice/podvlake razmakom
        .replace(/\b\w/g, c => c.toUpperCase()); // veliko početno slovo svake riječi
}

// Pokretanje servera
app.listen(PORT, () => {
    console.log(`Server pokrenut na http://localhost:${PORT}`);
});