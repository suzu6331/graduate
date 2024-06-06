fetch('../data/2022/102022001.json')
    .then(response => response.json())
    .then(data => {
        const quiz = data.quizzes[0];
        document.getElementById('quiz-mondai').textContent = quiz.mondai;
    })
    .catch(error => console.error('Error loading JSON:', error));
