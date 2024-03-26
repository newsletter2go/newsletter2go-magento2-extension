version = ${shell php -r "echo str_replace('.', '_', json_decode(file_get_contents('Newsletter2Go/Export/composer.json'))->version);"}
outfile = Magento2_nl2go_$(version).zip

$(version): $(outfile)

$(outfile):
	zip -r  build.zip ./Newsletter2Go/*
	mv build.zip $(outfile)


clean:
	rm -rf tmp