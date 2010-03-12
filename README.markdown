[konstrukt.dk](http://www.konstrukt.dk/)
===

About
---

Konstrukt is a minimalistic framework which provides a foundation on which to build rather than a boxed solution to all problems. It focuses on the controller layer, and tries to encourage the developer to deal directly with the HTTP protocol instead of abstracting it away. Konstrukt uses a hierarchical controller pattern, which provides a greater level of flexibility than the popular routed front controller frameworks.

Key Aspects
---

* Controllers are resources
* URI-to-controller-mapping gives your application a logical structure
* Routing based on logic rather than rules
* Nested controllers supports composite view rendering

Design Goals
---

* Embrace HTTP rather than hide it
* Enable the programmer, rather than automating
* Favour aggregation over code-generation or config-files
* Encourage encapsulation, and deter use of global scope
* Limit focus to the controller layer

Getting Started
---

###Installing through PEAR

The easiest way to install Konstrukt, is through PEAR. In a console, type the following:

    sudo pear channel-discover pearhub.org
    sudo pear install pearhub/konstrukt

###Creating a new project

You can create a new project by cloning the starterpack under `/examples/`:

Download the newest version of konstrukt from: [http://github.com/troelskn/konstrukt/downloads](http://github.com/troelskn/konstrukt/downloads). Then copy `starterpack_default`:

    cp -R examples/starterpack_default /var/www/foo

Replace `/var/www/foo` with the location where you want your app to reside.
