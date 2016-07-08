version = 0_0_00
outfile = Magento2_nl2go_$(version).zip

$(version): $(outfile)

$(outfile):
	zip -r  build.zip ./Newsletter2Go/*
	mv build.zip $(outfile)


clean:
	rm -rf tmp
