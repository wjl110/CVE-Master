package org.apache.axis2.util;

import java.io.*;


public class MetaDataEntry implements Externalizable {

    private Object payload;
    private static final long serialVersionUID = 8978361069526299875L;
    private static final int REVISION_2 = 2;
    private static final int revisionID = 2;

    public MetaDataEntry(Object payload){
        this.payload = payload;
    }

    public void writeExternal(ObjectOutput o) throws IOException {

        ByteArrayOutputStream bout = new ByteArrayOutputStream();
        ObjectOutputStream oout = new ObjectOutputStream(bout);
        oout.writeObject(payload);
        byte[] bytes = bout.toByteArray();

        o.writeLong(serialVersionUID);
        o.writeInt(2);
        o.writeBoolean(true);
        o.writeBoolean(false);
        o.writeInt(bytes.length);
        o.write(bytes);
        o.writeObject(null);
        o.writeObject(null);
        o.writeObject(null);

    }

    public void readExternal(ObjectInput in) throws IOException, ClassNotFoundException {
        // we don't care
    }

}
