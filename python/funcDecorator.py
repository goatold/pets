def funcDec(f):
	def infunc(x):
		print("wrap head")
		print(f'calling from internal func given "{x}"')
		f(x)
		print("wrap tail")
	return infunc

@funcDec
def func(x):
	print(f"I'm func. Given '{x}'")

func("'dec f x'")
func('whatever')