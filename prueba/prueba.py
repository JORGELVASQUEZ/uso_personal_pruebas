#!/usr/bin/env python3
"""
Juego matemático CLI

Características:
- Genera operaciones aleatorias (+, -, *, /).
- Límite de tiempo por pregunta.
- Límite de intentos por pregunta.
- Resumen de puntuación al final.

Funciona en Windows y Unix (usa msvcrt en Windows para entrada con timeout).
"""
import random
import time
import sys

try:
	import msvcrt
	_HAS_MS = True
except Exception:
	_HAS_MS = False

# Intento de importar tkinter para interfaz gráfica
try:
	import tkinter as tk
	from tkinter import messagebox
	_HAS_TK = True
except Exception:
	_HAS_TK = False


def input_with_timeout(prompt: str, timeout: float) -> str:
	"""Read input from the user with a timeout (seconds).

	Works on Windows (msvcrt) and on Unix (fallback to simple input if msvcrt not available,
	but the Unix fallback will still block - on Unix systems you can adapt to use select).
	"""
	if timeout is None or timeout <= 0:
		return input(prompt)

	if _HAS_MS:
		sys.stdout.write(prompt)
		sys.stdout.flush()
		end_time = time.time() + timeout
		buf = []
		while time.time() < end_time:
			if msvcrt.kbhit():
				ch = msvcrt.getwche()
				if ch in ('\r', '\n'):
					sys.stdout.write('\n')
					return ''.join(buf)
				# backspace
				if ch == '\x08':
					if buf:
						buf.pop()
						# move cursor back, write space, move back again
						sys.stdout.write('\b \b')
					continue
				buf.append(ch)
			time.sleep(0.01)
		# timed out
		raise TimeoutError()
	else:
		# Fallback: on non-Windows, try a simple approach that may block.
		# This will not enforce the timeout but keeps the script portable.
		# On Unix you can replace this with select or signal.
		print(f"(Tiempo máximo: {timeout:.1f}s) ")
		start = time.time()
		try:
			ans = input(prompt)
		except Exception:
			raise TimeoutError()
		elapsed = time.time() - start
		if elapsed > timeout:
			raise TimeoutError()
		return ans


def generate_problem(max_value=20):
	ops = ['+', '-', '*', '/']
	op = random.choice(ops)
	if op == '+':
		a = random.randint(0, max_value)
		b = random.randint(0, max_value)
		answer = a + b
	elif op == '-':
		a = random.randint(0, max_value)
		b = random.randint(0, a)  # avoid negative results for simplicity
		answer = a - b
	elif op == '*':
		a = random.randint(0, max_value // 2)
		b = random.randint(0, max_value // 2)
		answer = a * b
	else:  # '/'
		b = random.randint(1, max_value // 2 or 1)
		q = random.randint(0, max_value // 2)
		a = b * q
		answer = q

	expr = f"{a} {op} {b}"
	return expr, answer


def play_round(time_limit: float, attempts: int):
	expr, answer = generate_problem()
	print('\nResuelve: ', expr)
	print(f'Tienes {attempts} intento(s) y {time_limit:.1f} segundos en total para esta pregunta.')
	start = time.time()
	for attempt in range(1, attempts + 1):
		remaining = time_limit - (time.time() - start)
		if remaining <= 0:
			print('Se acabó el tiempo. No hubo respuesta a tiempo.')
			return False, False  # timed out, not correct

		try:
			s = input_with_timeout(f'Intento {attempt}/{attempts} (tiempo restante {remaining:.1f}s): ', remaining)
		except TimeoutError:
			print('\nTiempo terminado durante la entrada.')
			return False, False

		s = s.strip()
		if s.lower() in ('q', 'salir'):
			return None, None  # user requested quit

		# try to parse as integer
		try:
			val = int(s)
		except Exception:
			try:
				val = float(s)
			except Exception:
				print('Entrada no válida. Intenta con números.')
				continue

		if val == answer:
			print('¡Correcto!')
			return True, True
		else:
			print('Incorrecto.')
			# continue to next attempt

	print(f'Agotaste los intentos. La respuesta correcta era: {answer}')
	return False, True


class MathGameGUI:
	"""Interfaz gráfica para el juego matemático usando Tkinter."""

	def __init__(self, root):
		self.root = root
		root.title('Juego matemático - GUI')

		# Estado del juego
		self.time_limit = 10.0
		self.attempts = 2
		self.current_answer = None
		self.rounds = 0
		self.correct = 0
		self.timed_out = 0
		self.remaining_attempts = 0
		self.round_active = False
		self.timer_id = None
		self.round_end_time = None

		# Frames
		frm_settings = tk.Frame(root)
		frm_settings.pack(padx=8, pady=6, fill='x')

		tk.Label(frm_settings, text='Tiempo por pregunta (s):').grid(row=0, column=0, sticky='w')
		self.ent_time = tk.Entry(frm_settings, width=6)
		self.ent_time.insert(0, '10')
		self.ent_time.grid(row=0, column=1, sticky='w')

		tk.Label(frm_settings, text='Intentos:').grid(row=0, column=2, sticky='w', padx=(8,0))
		self.ent_attempts = tk.Entry(frm_settings, width=4)
		self.ent_attempts.insert(0, '2')
		self.ent_attempts.grid(row=0, column=3, sticky='w')

		self.btn_start = tk.Button(frm_settings, text='Iniciar juego', command=self.start_game)
		self.btn_start.grid(row=0, column=4, padx=(10,0))

		# Juego
		frm_game = tk.Frame(root)
		frm_game.pack(padx=8, pady=6, fill='x')

		self.lbl_expr = tk.Label(frm_game, text='Presiona "Iniciar juego" para comenzar', font=('Arial', 14))
		self.lbl_expr.pack(anchor='w')

		self.lbl_timer = tk.Label(frm_game, text='Tiempo: --', font=('Arial', 10))
		self.lbl_timer.pack(anchor='w')

		ent_frame = tk.Frame(frm_game)
		ent_frame.pack(fill='x', pady=(6,0))
		tk.Label(ent_frame, text='Tu respuesta:').pack(side='left')
		self.ent_answer = tk.Entry(ent_frame)
		self.ent_answer.pack(side='left', padx=(6,0))
		self.ent_answer.bind('<Return>', self.submit_answer)

		self.btn_submit = tk.Button(ent_frame, text='Enviar', command=self.submit_answer)
		self.btn_submit.pack(side='left', padx=(6,0))

		self.lbl_feedback = tk.Label(root, text='', fg='green')
		self.lbl_feedback.pack(anchor='w', padx=8)

		btns = tk.Frame(root)
		btns.pack(padx=8, pady=(6,8), fill='x')
		self.btn_next = tk.Button(btns, text='Siguiente', command=self.next_round, state='disabled')
		self.btn_next.pack(side='left')
		self.btn_end = tk.Button(btns, text='Terminar juego', command=self.end_game, state='disabled')
		self.btn_end.pack(side='left', padx=(6,0))

		self.lbl_stats = tk.Label(root, text='Rondas: 0 | Correctas: 0 | Tiempos agotados: 0')
		self.lbl_stats.pack(anchor='w', padx=8, pady=(4,8))

	def start_game(self):
		try:
			self.time_limit = float(self.ent_time.get())
		except Exception:
			self.time_limit = 10.0
		try:
			self.attempts = int(self.ent_attempts.get())
		except Exception:
			self.attempts = 2

		self.rounds = 0
		self.correct = 0
		self.timed_out = 0
		self.lbl_feedback.config(text='')
		self.btn_end.config(state='normal')
		self.btn_start.config(state='disabled')
		self.next_round()

	def start_round(self):
		expr, answer = generate_problem()
		self.current_answer = answer
		self.remaining_attempts = self.attempts
		self.round_active = True
		self.rounds += 1
		self.lbl_expr.config(text=f'Resolve: {expr}')
		self.lbl_feedback.config(text='')
		self.ent_answer.delete(0, 'end')
		self.ent_answer.config(state='normal')
		self.btn_submit.config(state='normal')
		self.btn_next.config(state='disabled')
		self.round_end_time = time.time() + self.time_limit
		self.update_timer()
		self.update_stats()

	def update_timer(self):
		if not self.round_active:
			self.lbl_timer.config(text='Tiempo: --')
			return
		remaining = self.round_end_time - time.time()
		if remaining <= 0:
			self.round_active = False
			self.ent_answer.config(state='disabled')
			self.btn_submit.config(state='disabled')
			self.lbl_feedback.config(text=f'Tiempo agotado. La respuesta era: {self.current_answer}', fg='red')
			self.timed_out += 1
			self.btn_next.config(state='normal')
			self.update_stats()
			return
		else:
			self.lbl_timer.config(text=f'Tiempo: {remaining:.1f}s')
			# actualiza cada 100 ms
			self.timer_id = self.root.after(100, self.update_timer)

	def submit_answer(self, event=None):
		if not self.round_active:
			return
		s = self.ent_answer.get().strip()
		try:
			val = float(s)
		except Exception:
			self.lbl_feedback.config(text='Entrada no válida. Usa números.', fg='orange')
			return

		# Comparación: los problemas devuelven respuestas enteras en esta versión
		if abs(val - float(self.current_answer)) < 1e-9:
			self.correct += 1
			self.round_active = False
			self.ent_answer.config(state='disabled')
			self.btn_submit.config(state='disabled')
			self.lbl_feedback.config(text='¡Correcto!', fg='green')
			self.btn_next.config(state='normal')
			if self.timer_id:
				try:
					self.root.after_cancel(self.timer_id)
				except Exception:
					pass
			self.update_stats()
			return
		else:
			self.remaining_attempts -= 1
			if self.remaining_attempts <= 0:
				self.round_active = False
				self.ent_answer.config(state='disabled')
				self.btn_submit.config(state='disabled')
				self.lbl_feedback.config(text=f'Incorrecto. La respuesta era: {self.current_answer}', fg='red')
				self.btn_next.config(state='normal')
				if self.timer_id:
					try:
						self.root.after_cancel(self.timer_id)
					except Exception:
						pass
				self.update_stats()
				return
			else:
				self.lbl_feedback.config(text=f'Incorrecto. Intentos restantes: {self.remaining_attempts}', fg='orange')

	def next_round(self):
		self.start_round()

	def end_game(self):
		self.round_active = False
		if self.timer_id:
			try:
				self.root.after_cancel(self.timer_id)
			except Exception:
				pass
		summary = f'Rondas: {self.rounds}\nCorrectas: {self.correct}\nTiempos agotados: {self.timed_out}'
		messagebox.showinfo('Resumen', summary)
		self.btn_start.config(state='normal')
		self.btn_end.config(state='disabled')
		self.btn_next.config(state='disabled')

	def update_stats(self):
		self.lbl_stats.config(text=f'Rondas: {self.rounds} | Correctas: {self.correct} | Tiempos agotados: {self.timed_out}')


def main():
	# Si se solicita la interfaz gráfica por argumento o el usuario lo elige, lanzarla
	use_gui = any('gui' in a.lower() for a in sys.argv[1:])
	if not use_gui:
		try:
			ans = input('Abrir interfaz gráfica? (s/n, por defecto s): ').strip().lower() or 's'
			if ans in ('s', 'si', 'y', 'yes'):
				use_gui = True
		except Exception:
			use_gui = False

	if use_gui and _HAS_TK:
		root = tk.Tk()
		app = MathGameGUI(root)
		root.mainloop()
		return
	elif use_gui and not _HAS_TK:
		print('Tkinter no está disponible en este entorno. Se continuará en modo CLI.')

	# Modo CLI por defecto
	print('Juego matemático rápido (modo consola)')
	try:
		time_limit = float(input('Tiempo por pregunta (segundos, por defecto 10): ') or 10)
	except Exception:
		time_limit = 10.0

	try:
		attempts = int(input('Intentos por pregunta (por defecto 2): ') or 2)
	except Exception:
		attempts = 2

	rounds = 0
	correct = 0
	timed_out = 0

	while True:
		res = play_round(time_limit, attempts)
		if res == (None, None):
			print('Saliendo del juego...')
			break
		is_correct, used_attempts = res
		rounds += 1
		if is_correct:
			correct += 1
		else:
			# if not correct and used_attempts is False, it means timed out
			if used_attempts is False:
				timed_out += 1

		# ask to continue
		cont = input('Otra ronda? (s/n): ').strip().lower()
		if cont not in ('s', 'si', 'y', 'yes'):
			break

	print('\nResumen:')
	print(f'Rondas jugadas: {rounds}')
	print(f'Correctas: {correct}')
	print(f'Tiempo agotado en: {timed_out}')
	print('Gracias por jugar!')


if __name__ == '__main__':
	main()

