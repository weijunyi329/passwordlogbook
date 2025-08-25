using System;
using System.IO;

namespace PasswordLogbookWin
{
    public class TarSubStream : Stream
    {
        private readonly Stream _parentStream;
        private readonly long _startOffset;
        private readonly long _length;
        private long _position;

        public TarSubStream(Stream parentStream, long startOffset, long length)
        {
            if (parentStream == null) throw new ArgumentNullException(nameof(parentStream));
            if (!parentStream.CanSeek) throw new ArgumentException("父流必须支持 Seek 操作。", nameof(parentStream));
            if (startOffset < 0) throw new ArgumentOutOfRangeException(nameof(startOffset), "起始偏移量不能为负数。");
            if (length < 0) throw new ArgumentOutOfRangeException(nameof(length), "长度不能为负数。");
            if (startOffset + length > parentStream.Length) throw new ArgumentException("子流的范围超出了父流的长度。");

            _parentStream = parentStream;
            _startOffset = startOffset;
            _length = length;
            _position = 0;
        }

        public override bool CanRead => _parentStream.CanRead;
        public override bool CanSeek => _parentStream.CanSeek;
        public override bool CanWrite => false;
        public override long Length => _length;

        public override long Position
        {
            get => _position;
            set
            {
                if (value < 0 || value > _length)
                    throw new ArgumentOutOfRangeException(nameof(value), "位置超出子流范围。");
                _position = value;
            }
        }

        public override void Flush()
        {
            // 子流不支持写入，所以 Flush 不做任何事
        }

        public override int Read(byte[] buffer, int offset, int count)
        {
            if (_position >= _length)
                return 0; // 已到达子流末尾

            // 确保不会读取超过子流末尾的数据
            long bytesToRead = Math.Min(count, _length - _position);

            // 将父流的指针移动到正确的位置
            _parentStream.Seek(_startOffset + _position, SeekOrigin.Begin);

            // 从父流中读取数据
            int bytesRead = _parentStream.Read(buffer, offset, (int)bytesToRead);

            // 更新子流的位置
            _position += bytesRead;
            return bytesRead;
        }

        public override long Seek(long offset, SeekOrigin origin)
        {
            long newPosition;
            switch (origin)
            {
                case SeekOrigin.Begin:
                    newPosition = offset;
                    break;
                case SeekOrigin.Current:
                    newPosition = _position + offset;
                    break;
                case SeekOrigin.End:
                    newPosition = _length + offset;
                    break;
                default:
                    throw new ArgumentOutOfRangeException(nameof(origin));
            }

            if (newPosition < 0 || newPosition > _length)
                throw new IOException("尝试在子流中定位到无效位置。");

            _position = newPosition;
            return _position;
        }

        public override void SetLength(long value)
        {
            throw new NotSupportedException();
        }

        public override void Write(byte[] buffer, int offset, int count)
        {
            throw new NotSupportedException();
        }
    }
}