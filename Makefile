version = ${shell php -r "echo str_replace('.', '_', json_decode(file_get_contents('Newsletter2Go/Export/composer.json'))->version);"}
outfile = Magento2_nl2go_$(version).zip
src = $(wildcard Newsletter2Go/*/*.php)

$(outfile): $(src)
    zip -r -e build.zip ./Newsletter2Go/*
    mv build.zip $(outfile)

clean:
    rm -rf tmp $(outfile)
