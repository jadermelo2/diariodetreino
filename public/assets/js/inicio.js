(function () {
  'use strict';

  var streakEl = document.getElementById('home-streak');
  var exerciciosEl = document.getElementById('home-exercicios');
  var diasEl = document.getElementById('home-dias');

  fetch('api/calendar.php')
    .then(function (res) { return res.json(); })
    .then(function (dados) {
      streakEl.textContent = dados.streak_atual ?? 0;
      diasEl.textContent = dados.total_dias_treinados ?? 0;
    })
    .catch(function () {
      streakEl.textContent = '0';
      diasEl.textContent = '0';
    });

  fetch('api/exercises.php')
    .then(function (res) { return res.json(); })
    .then(function (exercicios) {
      exerciciosEl.textContent = Array.isArray(exercicios) ? exercicios.length : 0;
    })
    .catch(function () {
      exerciciosEl.textContent = '0';
    });
})();
